<?php

/**
 * Name: Timeline Filter
 * Description: Filters hashtags and words in personal timelines
 * Version: 1.4
 * Author: Matthias Ebers <https://loma.ml/profile/feb>
 */

use Friendica\Core\Hook;
use Friendica\Core\Renderer;
use Friendica\DI;

function timelinefilter_install()
{
	Hook::register('page_end', 'addon/timelinefilter/timelinefilter.php', 'timelinefilter_page_end', 999);
	Hook::register('addon_settings', 'addon/timelinefilter/timelinefilter.php', 'timelinefilter_addon_settings');
	Hook::register('addon_settings_post', 'addon/timelinefilter/timelinefilter.php', 'timelinefilter_addon_settings_post');
}

function timelinefilter_addon_settings(&$data)
{
	$uid = DI::userSession()->getLocalUserId();
	if (!$uid) {
		return;
	}

	$enabled = !DI::pConfig()->get($uid, 'timelinefilter', 'disable', 1);
	$rules = timelinefilter_get_rules($uid);

	if (empty($rules)) {
		$rules[] = ['keyword' => '', 'type' => 'hashtag', 'duration' => 'always', 'expires' => 0];
	}

	$t = Renderer::getMarkupTemplate('settings.tpl', 'addon/timelinefilter/');
	$html = Renderer::replaceMacros($t, [
		'$info'        => DI::l10n()->t('Safe Filter: Define personal rules with optional expiration dates to hide posts.'),
		'$enabled'     => ['timelinefilter-enable', DI::l10n()->t('Enable Filter'), $enabled],
		'$words_label' => DI::l10n()->t('Filter Rules'),
		'$words_help'  => DI::l10n()->t('Add keywords, select type and specify how long the filter should remain active.'),
		'$rules'       => $rules,
		'$submit'      => DI::l10n()->t('Save Settings')
	]);

	$data = [
		'addon' => 'timelinefilter',
		'title' => DI::l10n()->t('Timeline Filter'),
		'html'  => $html,
	];
}

function timelinefilter_addon_settings_post(&$b)
{
	$uid = DI::userSession()->getLocalUserId();
	if (!$uid || empty($_POST['timelinefilter-submit'])) {
		return;
	}

	$enable = isset($_POST['timelinefilter-enable']) ? intval($_POST['timelinefilter-enable']) : 0;
	$disable = $enable ? 0 : 1;

	$keywords  = $_POST['tf-keywords'] ?? [];
	$types     = $_POST['tf-types'] ?? [];
	$durations = $_POST['tf-durations'] ?? [];
	$expires   = $_POST['tf-expires'] ?? [];

	$rules = [];
	$now = time();

	foreach ($keywords as $i => $kw) {
		$kw = trim($kw);
		if ($kw === '') {
			continue;
		}

		$duration = $durations[$i] ?? 'always';
		$exp = intval($expires[$i] ?? 0);

		if ($exp === 0) {
			if ($duration === '1w') {
				$exp = $now + (7 * 86400);
			} elseif ($duration === '1m') {
				$exp = $now + (30 * 86400);
			}
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

function timelinefilter_page_end(&$html)
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
	$words = [];

	foreach ($rules as $r) {
		$kw = mb_strtolower($r['keyword']);
		if ($r['type'] === 'hashtag') {
			$hashtags[] = ltrim($kw, '#');
		} else {
			$words[] = $kw;
		}
	}

	if (empty($hashtags) && empty($words)) {
		return;
	}

	$script = '<script>
	(function() {
		"use strict";

		var hashtags = ' . json_encode($hashtags) . ';
		var words = ' . json_encode($words) . ';
		var POST_SELECTOR = "article, .thread-wrapper, .wall-item-container";

		function filterPost(post) {
			if (!post || post.nodeType !== 1 || post.dataset.tfFiltered) {
				return;
			}

			post.dataset.tfFiltered = "true";
			var text = post.textContent.toLowerCase();

			for (var i = 0; i < words.length; i++) {
				if (text.indexOf(words[i]) !== -1) {
					post.style.setProperty("display", "none", "important");
					return;
				}
			}

			if (hashtags.length > 0) {
				for (var j = 0; j < hashtags.length; j++) {
					if (text.indexOf("#" + hashtags[j]) !== -1) {
						post.style.setProperty("display", "none", "important");
						return;
					}
				}

				var links = post.querySelectorAll("a[href]");
				for (var k = 0; k < links.length; k++) {
					var href = links[k].getAttribute("href").toLowerCase();
					for (var l = 0; l < hashtags.length; l++) {
						if (href.indexOf("tag/" + hashtags[l]) !== -1 || href.indexOf("tag=" + hashtags[l]) !== -1) {
							post.style.setProperty("display", "none", "important");
							return;
						}
					}
				}
			}
		}

		var posts = document.querySelectorAll(POST_SELECTOR);
		for (var i = 0; i < posts.length; i++) {
			filterPost(posts[i]);
		}

		var target = document.getElementById("threads-location") || document.body;
		var observer = new MutationObserver(function(mutations) {
			mutations.forEach(function(m) {
				m.addedNodes.forEach(function(node) {
					if (node.nodeType !== 1) return;
					if (node.matches && node.matches(POST_SELECTOR)) {
						filterPost(node);
					} else if (node.querySelectorAll) {
						var subPosts = node.querySelectorAll(POST_SELECTOR);
						for (var x = 0; x < subPosts.length; x++) {
							filterPost(subPosts[x]);
						}
					}
				});
			});
		});

		observer.observe(target, { childList: true, subtree: true });
	})();
	</script>';

	$html .= $script;
}

/**
 * Helper to fetch rules and clear expired ones
 */
function timelinefilter_get_rules($uid)
{
	$json = DI::pConfig()->get($uid, 'timelinefilter', 'rules', '[]');
	$rules = json_decode($json, true);
	if (!is_array($rules)) {
		return [];
	}

	$now = time();
	$clean = [];
	$changed = false;

	foreach ($rules as $r) {
		if (!empty($r['expires']) && $r['expires'] > 0 && $now > $r['expires']) {
			$changed = true;
			continue;
		}
		$clean[] = $r;
	}

	if ($changed) {
		DI::pConfig()->set($uid, 'timelinefilter', 'rules', json_encode($clean));
	}

	return $clean;
}
