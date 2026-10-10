<?php

/**
 * Name: Profile Guard
 * Description: Restricts selected anonymous cross-origin browser requests to a user's data via Friendica's Mastodon-compatible client API
 * Version: 2.1.1
 * Author: Jools <https://friendica.de/profile/jools>
 * Author: Modification - feb <https://loma.ml/profile/feb>
 */

// SPDX-FileCopyrightText: 2026 Jools <https://friendica.de/profile/jools>
//
// SPDX-License-Identifier: AGPL-3.0-or-later

use Friendica\App\Router;
use Friendica\Capabilities\ICanCreateResponses;
use Friendica\Core\Hook;
use Friendica\Core\Renderer;
use Friendica\Core\System;
use Friendica\Database\DBA;
use Friendica\DI;
use Friendica\Model\Contact;
use Friendica\Model\Post;
use Friendica\Model\User;
use Friendica\Module\BaseApi;
use Friendica\Module\Response;

/**
 * Static routes below api/v1/accounts/ that carry no account name. The router
 * serves them before the {name} route, so they must never be treated as a name.
 * Taken from static/routes.config.php (identical in 2026.05 and 2026.08-rc).
 */
const PROFILEGUARD_FIXED_ACCOUNT_ROUTES = [
	'familiar_followers',
	'lookup',
	'relationships',
	'search',
	'update_credentials',
	'verify_credentials',
];

/**
 * Determines whether a request path is one of the protected Mastodon API routes.
 *
 * Protected by numeric id are the post list of an account and a single post
 * with its boost and favourite lists. The account name route is matched as
 * well; it only counts if the name denotes a protected local user (see
 * profileguard_local_nick()). Account profiles by id, thread contexts and link
 * cards are deliberately left out.
 *
 * The command must be exactly what the router sees (DI::args()->getCommand()):
 * without leading slash and without query string.
 *
 * @param string $command
 * @return array|null ['type' => 'account-statuses'|'status', 'id' => int] or ['type' => 'account-name', 'name' => string]
 */
function profileguard_match_path(string $command): ?array
{
	$parts = explode('/', $command);
	$count = count($parts);

	if ($count === 4 && $parts[0] === 'api' && $parts[1] === 'v1' && $parts[2] === 'accounts'
		&& $parts[3] !== '' && !ctype_digit($parts[3]) && !in_array($parts[3], PROFILEGUARD_FIXED_ACCOUNT_ROUTES, true)) {
		return ['type' => 'account-name', 'name' => $parts[3]];
	}

	if ($count < 4 || $count > 5 || $parts[0] !== 'api' || $parts[1] !== 'v1' || !ctype_digit($parts[3])) {
		return null;
	}

	if ($parts[2] === 'accounts' && $count === 5 && $parts[4] === 'statuses') {
		return ['type' => 'account-statuses', 'id' => (int) $parts[3]];
	}

	if ($parts[2] === 'statuses' && ($count === 4 || in_array($parts[4], ['reblogged_by', 'favourited_by'], true))) {
		return ['type' => 'status', 'id' => (int) $parts[3]];
	}

	return null;
}

/**
 * Returns the local nickname a name on the account name route stands for, or null.
 *
 * Mirrors how the core resolves such a name, without querying anything: the
 * database collation ignores case and trailing spaces, a lowercase "acct:"
 * prefix is removed (only effective for an address), and an address only
 * counts if its host is this instance's host. The result must be a valid
 * Friendica nickname, otherwise null is returned and the request passes.
 *
 * @param string $name    Name segment as seen by the router
 * @param string $ownHost This instance's host name
 * @return string|null
 */
function profileguard_local_nick(string $name, string $ownHost): ?string
{
	$name = rtrim($name, ' ');
	$acct = str_starts_with($name, 'acct:');
	if ($acct) {
		$name = substr($name, 5);
	}
	$name = strtolower($name);

	if (str_contains($name, '@')) {
		$parts = explode('@', $name);
		if (count($parts) !== 2 || $ownHost === '' || $parts[1] !== strtolower($ownHost)) {
			return null;
		}
		$name = $parts[0];
	} elseif ($acct) {
		return null;
	}

	return preg_match('/^[a-z0-9][a-z0-9_]*$/', $name) ? $name : null;
}

/**
 * Normalizes an origin or base URL to "scheme://host[:port]".
 *
 * Default ports are dropped, the host is lowercased. Anything that is not a
 * plain http(s) origin, including the opaque origin "null", returns null.
 *
 * @param string $url
 * @return string|null
 */
function profileguard_normalize_origin(string $url): ?string
{
	$parts = parse_url(trim($url));
	if (!is_array($parts) || empty($parts['scheme']) || empty($parts['host']) || isset($parts['user']) || isset($parts['pass'])) {
		return null;
	}

	$scheme = strtolower($parts['scheme']);
	if (!in_array($scheme, ['http', 'https'], true)) {
		return null;
	}

	$origin = $scheme . '://' . strtolower($parts['host']);
	if (isset($parts['port']) && !(($scheme === 'http' && $parts['port'] === 80) || ($scheme === 'https' && $parts['port'] === 443))) {
		$origin .= ':' . $parts['port'];
	}

	return $origin;
}

/**
 * Checks whether a request is an actual HTTP GET issued by a script of another
 * website in a browser (fetch/XHR in CORS mode, cross-site).
 *
 * All criteria are required. They match an observed anonymous cross-origin
 * browser request. If any of them is missing or different, the
 * request is not considered such a request and passes unchanged.
 *
 * @param array  $server    The $_SERVER array
 * @param string $ownOrigin This instance's base URL
 * @return bool
 */
function profileguard_is_foreign_browser_get(array $server, string $ownOrigin): bool
{
	// HEAD is normalized to GET by Friendica, so the original method is checked here
	if (($server['REQUEST_METHOD'] ?? '') !== 'GET') {
		return false;
	}

	$own    = profileguard_normalize_origin($ownOrigin);
	$origin = profileguard_normalize_origin((string) ($server['HTTP_ORIGIN'] ?? ''));
	if ($own === null || $origin === null || $origin === $own) {
		return false;
	}

	$header = fn (string $key): string => strtolower(trim((string) ($server[$key] ?? '')));

	return $header('HTTP_SEC_FETCH_SITE') === 'cross-site'
		&& $header('HTTP_SEC_FETCH_MODE') === 'cors'
		&& $header('HTTP_SEC_FETCH_DEST') === 'empty';
}

function profileguard_install(): void
{
	Hook::register('init_1', 'addon/profileguard/profileguard.php', 'profileguard_init_1');
	Hook::register('addon_settings', 'addon/profileguard/profileguard.php', 'profileguard_addon_settings');
	Hook::register('addon_settings_post', 'addon/profileguard/profileguard.php', 'profileguard_addon_settings_post');
}

function profileguard_addon_settings(array &$data): void
{
    $uid = DI::userSession()->getLocalUserId();
    if (!$uid) {
        return;
    }

    // Standard ist jetzt true (Opt-out)
    $enabled = (bool) DI::pConfig()->get($uid, 'profileguard', 'enabled', true);

	$t    = Renderer::getMarkupTemplate('settings.tpl', 'addon/profileguard/');
	$html = Renderer::replaceMacros($t, [
		'$enabled' => [
			'profileguard-enabled',
			DI::l10n()->t("Shields your data from anonymous scraping via the Mastodon-compatible API. This protection is automatically active for all users - disable it here if you don't want it."),
			$enabled,
			'',
		],
	]);

    $data = [
        'addon' => 'profileguard',
        'title' => DI::l10n()->t('Profile Guard'),
        'html'  => $html,
    ];
}

function profileguard_addon_settings_post(array &$post): void
	{
		$uid = DI::userSession()->getLocalUserId();
		if (!$uid || empty($post['profileguard-submit'])) {
			return;
		}

		$enabled = !empty($post['profileguard-enabled']);
		DI::pConfig()->set($uid, 'profileguard', 'enabled', $enabled);
	}

/**
 * Public contact ids of all local users who enabled the guard.
 * Removed and blocked users are skipped. Computed once per request.
 *
 * @return array [public contact id => user id]
 */
function profileguard_protected_contacts(): array
{
    static $contacts = null;

    if ($contacts !== null) {
        return $contacts;
    }

    $found = [];
    $rows = DBA::select('user', ['uid', 'blocked', 'account_removed']);
    while ($row = DBA::fetch($rows)) {
        $uid = (int) $row['uid'];
        if ($row['blocked'] || $row['account_removed']) {
            continue;
        }

        $enabled = (bool) DI::pConfig()->get($uid, 'profileguard', 'enabled', true);
        if (!$enabled) {
            continue;
        }

        $cid = Contact::getPublicIdByUserId($uid);
        if ($cid) {
            $found[(int) $cid] = $uid;
        }
    }
    DBA::close($rows);

    return $contacts = $found;
}

/**
 * Returns the public contact id that owns the requested resource, or 0.
 *
 * For posts this reads the same row the core reads for an anonymous request
 * (Post::selectFirst with uid 0). Its author-id is the owner. A reblog is owned
 * by the account that reblogged, so the original's author is not considered.
 *
 * @param array $match result of profileguard_match_path()
 * @return int
 */
function profileguard_owner(array $match): int
{
	if ($match['type'] === 'account-statuses') {
		return $match['id'];
	}

	$post = Post::selectFirst(['author-id'], ['uri-id' => $match['id'], 'uid' => 0]);

	return (int) ($post['author-id'] ?? 0);
}

/**
 * Returns the user id if the nickname belongs to exactly one local user and
 * that user is protected, otherwise 0.
 *
 * Reads the user table only. No contact lookup, so a remote contact with the
 * same nick is never involved, and the result does not depend on which
 * contact the core would pick for that nick.
 *
 * @param string $nick      Normalized nickname (profileguard_local_nick())
 * @param array  $protected Result of profileguard_protected_contacts()
 * @return int
 */
function profileguard_protected_nick_uid(string $nick, array $protected): int
{
	if (DBA::count('user', ['nickname' => $nick]) !== 1) {
		return 0;
	}

	$user = User::getByNickname($nick, ['uid']);
	$uid  = (int) ($user['uid'] ?? 0);

	return in_array($uid, $protected, true) ? $uid : 0;
}

/**
 * Answers with 401 in the Mastodon error format and ends the request.
 */
function profileguard_reject(int $uid, string $command, string $origin): void
{
	DI::logger()->debug('profileguard: browser request blocked', ['uid' => $uid, 'command' => $command, 'origin' => $origin]);

	$response = new Response();
	$response->setStatus(401, 'Unauthorized');
	$response->setType(ICanCreateResponses::TYPE_JSON);
	$response->setHeader('*', 'Access-Control-Allow-Origin');
	$response->addContent(json_encode(['error' => 'This resource requires authentication']));

	System::echoResponse($response->generate());
	System::exit();
}

/**
 * Blocks anonymous cross-site browser requests for the posts of users who
 * enabled the guard, and for their local account name. Any other request, and
 * any request during which the check fails, passes unchanged.
 */
function profileguard_init_1(): void
{
	$uid     = 0;
	$command = '';

	try {
		$args = DI::args();
		if ($args->getMethod() !== Router::GET) {
			return;
		}

		$command = $args->getCommand();
		$match   = profileguard_match_path($command);
		if ($match === null) {
			return;
		}

		if (!profileguard_is_foreign_browser_get($_SERVER, (string) DI::baseUrl())) {
			return;
		}

		$protected = profileguard_protected_contacts();
		if (empty($protected)) {
			return;
		}

		// Same check the API modules use: OAuth bearer token or HTTP basic authentication
		if (BaseApi::getCurrentUserID() !== 0) {
			return;
		}

		if ($match['type'] === 'account-name') {
			$nick = profileguard_local_nick($match['name'], DI::baseUrl()->getHost());
			$uid  = $nick === null ? 0 : profileguard_protected_nick_uid($nick, $protected);
		} else {
			$uid = $protected[profileguard_owner($match)] ?? 0;
		}
	} catch (\Throwable $e) {
		DI::logger()->warning('profileguard: check failed, request passed through', ['exception' => $e]);
		return;
	}

	if ($uid) {
		profileguard_reject($uid, $command, (string) ($_SERVER['HTTP_ORIGIN'] ?? ''));
	}
}
