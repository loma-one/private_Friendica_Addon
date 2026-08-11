<?php

/**
 * Name: Timeline Filter
 * Description: Filters hashtags, words and accounts in personal timelines
 * Version: 1.5.3
 * Author: Matthias Ebers <https://loma.ml/profile/feb>
 */

use Friendica\Core\Hook;
use Friendica\Core\Renderer;
use Friendica\DI;

function timelinefilter_install(): void
{
    Hook::register('page_end', 'addon/timelinefilter/timelinefilter.php', 'timelinefilter_page_end', 999);
    Hook::register('addon_settings', 'addon/timelinefilter/timelinefilter.php', 'timelinefilter_addon_settings');
    Hook::register('addon_settings_post', 'addon/timelinefilter/timelinefilter.php', 'timelinefilter_addon_settings_post');
}

function timelinefilter_addon_settings(array &$data): void
{
    $uid = DI::userSession()->getLocalUserId();
    if (!$uid) {
        return;
    }

    $enabled = !DI::pConfig()->get($uid, 'timelinefilter', 'disable', 1);
    $rules   = timelinefilter_get_rules($uid);

    if (empty($rules)) {
        $rules[] = [
            'keyword'        => '',
            'type'           => 'hashtag',
            'duration'       => 'always',
            'expires'        => 0,
            'days_left'      => null,
            'days_left_text' => '',
        ];
    } else {
        $now = time();
        foreach ($rules as &$rule) {
            if (!empty($rule['expires']) && $rule['expires'] > $now) {
                $days                   = (int) ceil(($rule['expires'] - $now) / 86400);
                $rule['days_left']      = $days;
                $rule['days_left_text'] = sprintf(DI::l10n()->tt('%d day remaining', '%d days remaining', $days), $days);
            } else {
                $rule['days_left']      = null;
                $rule['days_left_text'] = '';
            }
        }
        unset($rule);
    }

    $template = Renderer::getMarkupTemplate('settings.tpl', 'addon/timelinefilter/');
    $html     = Renderer::replaceMacros($template, [
        '$info'        => DI::l10n()->t('Safe Filter: Define personal rules with optional expiration dates to hide posts.'),
        '$enabled'     => ['timelinefilter-enable', DI::l10n()->t('Enable Filter'), $enabled],
        '$words_label' => DI::l10n()->t('Filter Rules'),
        '$words_help'  => DI::l10n()->t('Add keywords/accounts, select type and specify how long the filter should remain active.'),
        '$rules'       => $rules,
        '$submit'      => DI::l10n()->t('Save Settings'),
        '$opt_always'  => DI::l10n()->t('Always'),
        '$opt_1d'      => DI::l10n()->t('1 Day'),
        '$opt_1w'      => DI::l10n()->t('1 Week'),
        '$opt_1m'      => DI::l10n()->t('1 Month'),
    ]);

    $data = [
        'addon' => 'timelinefilter',
        'title' => DI::l10n()->t('Timeline Filter'),
        'html'  => $html,
    ];
}

function timelinefilter_addon_settings_post(array &$b): void
{
    $uid = DI::userSession()->getLocalUserId();
    if (!$uid || empty($_POST['timelinefilter-submit'])) {
        return;
    }

    $disable   = !empty($_POST['timelinefilter-enable']) ? 0 : 1;
    $keywords  = $_POST['tf-keywords'] ?? [];
    $types     = $_POST['tf-types'] ?? [];
    $durations = $_POST['tf-durations'] ?? [];
    $expires   = $_POST['tf-expires'] ?? [];

    $rules = [];
    $now   = time();

    foreach ($keywords as $i => $kw) {
        $kw = trim($kw);
        if ($kw === '') {
            continue;
        }

        $duration = $durations[$i] ?? 'always';
        $exp      = (int) ($expires[$i] ?? 0);

        if ($exp === 0) {
            $exp = match ($duration) {
                '1d'    => $now + 86400,
                '1w'    => $now + (7 * 86400),
                '1m'    => $now + (30 * 86400),
                default => 0,
            };
        }

        $rules[] = [
            'keyword'  => $kw,
            'type'     => $types[$i] ?? 'hashtag',
            'duration' => $duration,
            'expires'  => $exp,
        ];
    }

    DI::pConfig()->set($uid, 'timelinefilter', 'rules', json_encode($rules));
    DI::pConfig()->set($uid, 'timelinefilter', 'disable', $disable);
}

function timelinefilter_page_end(string &$html): void
{
    $uid = DI::userSession()->getLocalUserId();
    if (!$uid || empty($html) || DI::pConfig()->get($uid, 'timelinefilter', 'disable', 1)) {
        return;
    }

    $rules = timelinefilter_get_rules($uid);
    if (empty($rules)) {
        return;
    }

    $hashtags = [];
    $words    = [];
    $accounts = [];

    foreach ($rules as $rule) {
        $kw = mb_strtolower($rule['keyword']);
        switch ($rule['type']) {
            case 'hashtag':
                $hashtags[] = ltrim($kw, '#');
                break;
            case 'account':
                $accounts[] = ltrim($kw, '@');
                break;
            default:
                $words[] = $kw;
                break;
        }
    }

    if (empty($hashtags) && empty($words) && empty($accounts)) {
        return;
    }

    $jsTemplate = <<<'JS'
<script>
(() => {
    "use strict";
    const config = {
        hashtags: {{HASHTAGS}},
        words: {{WORDS}},
        accounts: {{ACCOUNTS}},
        selector: "article, .thread-wrapper, .wall-item-container"
    };

    const escapeRegExp = (str) => str.replace(/[.*+?^${}()|[\]\\]/g, '\\$&');

    // Unicode Boundary Matching (\p{L} = Buchstaben, \p{N} = Zahlen)
    const wordRegexes = config.words.map(w =>
        new RegExp('(?<![\\p{L}\\p{N}])' + escapeRegExp(w) + '(?![\\p{L}\\p{N}])', 'ui')
    );

    const hashtagRegexes = config.hashtags.map(h =>
        new RegExp('(?<![\\p{L}\\p{N}_])#' + escapeRegExp(h) + '(?![\\p{L}\\p{N}_])', 'ui')
    );

    const filterPost = (post) => {
        if (!post || post.nodeType !== 1 || post.dataset.tfFiltered) return;
        post.dataset.tfFiltered = "true";

        const text = post.textContent;

        // 1. Exakter Wortfilter (Filtert "Blume", lässt "Blumenmeer" unangetastet)
        if (wordRegexes.some(rx => rx.test(text))) {
            post.style.setProperty("display", "none", "important");
            return;
        }

        // 2. Exakter Hashtagfilter
        if (hashtagRegexes.some(rx => rx.test(text))) {
            post.style.setProperty("display", "none", "important");
            return;
        }

        // 3. Hashtags in Link-Hrefs prüfen
        const links = Array.from(post.querySelectorAll("a[href]"));
        if (config.hashtags.length && links.some(a => {
            const href = a.getAttribute("href").toLowerCase();
            return config.hashtags.some(h => {
                const rx = new RegExp('tag[/=]' + escapeRegExp(h) + '(?![\\p{L}\\p{N}_])', 'i');
                return rx.test(href);
            });
        })) {
            post.style.setProperty("display", "none", "important");
            return;
        }

        // 4. Accounts filtern
        if (config.accounts.length) {
            const hasAccount = config.accounts.some(acc => {
                if (text.toLowerCase().includes("@" + acc.toLowerCase())) return true;

                const [uName, uDom] = acc.split("@");
                if (!uDom) return false;

                return links.some(a => {
                    const href = a.getAttribute("href").toLowerCase();
                    return href.includes(uDom) && (
                        href.includes("/profile/" + uName) ||
                        href.includes("/users/" + uName) ||
                        href.includes("/@" + uName)
                    );
                });
            });

            if (hasAccount) {
                post.style.setProperty("display", "none", "important");
            }
        }
    };

    document.querySelectorAll(config.selector).forEach(filterPost);

    const target = document.getElementById("threads-location") || document.body;
    new MutationObserver(mutations => {
        for (const m of mutations) {
            m.addedNodes.forEach(node => {
                if (node.nodeType !== 1) return;
                if (node.matches?.(config.selector)) filterPost(node);
                else node.querySelectorAll?.(config.selector).forEach(filterPost);
            });
        }
    }).observe(target, { childList: true, subtree: true });
})();
</script>
JS;

    $html .= str_replace(
        ['{{HASHTAGS}}', '{{WORDS}}', '{{ACCOUNTS}}'],
        [json_encode($hashtags), json_encode($words), json_encode($accounts)],
        $jsTemplate
    );
}

function timelinefilter_get_rules(int $uid): array
{
    $json  = DI::pConfig()->get($uid, 'timelinefilter', 'rules', '[]');
    $rules = json_decode($json, true);

    if (!is_array($rules)) {
        return [];
    }

    $now   = time();
    $clean = array_filter($rules, static function ($r) use ($now) {
        return empty($r['expires']) || $r['expires'] <= 0 || $r['expires'] > $now;
    });

    if (count($clean) !== count($rules)) {
        DI::pConfig()->set($uid, 'timelinefilter', 'rules', json_encode(array_values($clean)));
    }

    return array_values($clean);
}
