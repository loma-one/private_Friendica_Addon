<?php

/**
 * Name: Timeline Filter
 * Description: Filters hashtags and words in personal timelines
 * Version: 1.2
 * Author: Matthias Ebers  <https://loma.ml/profile/feb>
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

function timelinefilter_addon_settings(array &$data)
{
    if (!DI::userSession()->getLocalUserId()) {
        return;
    }

    $uid = DI::userSession()->getLocalUserId();
    $enabled = !DI::pConfig()->get($uid, 'timelinefilter', 'disable');

    $rules_json = DI::pConfig()->get($uid, 'timelinefilter', 'rules', '[]');
    $rules = json_decode($rules_json, true) ?: [];

    $current_time = time();
    $filtered_rules = [];
    $has_changed = false;

    foreach ($rules as $rule) {
        if (isset($rule['expires']) && $rule['expires'] > 0 && $current_time > $rule['expires']) {
            $has_changed = true;
            continue;
        }
        $filtered_rules[] = $rule;
    }

    if ($has_changed) {
        DI::pConfig()->set($uid, 'timelinefilter', 'rules', json_encode($filtered_rules));
        $rules = $filtered_rules;
    }

    if (empty($rules)) {
        $rules[] = ['keyword' => '', 'type' => 'hashtag', 'duration' => 'always', 'expires' => 0];
    }

    $t    = Renderer::getMarkupTemplate('settings.tpl', 'addon/timelinefilter/');
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

function timelinefilter_addon_settings_post(array &$b)
{
    if (!DI::userSession()->getLocalUserId()) {
        return;
    }

    if (!empty($_POST['timelinefilter-submit'])) {
        $uid = DI::userSession()->getLocalUserId();
        $enable = !empty($_POST['timelinefilter-enable']) ? intval($_POST['timelinefilter-enable']) : 0;
        $disable = 1 - $enable;

        $keywords  = $_POST['tf-keywords'] ?? [];
        $types     = $_POST['tf-types'] ?? [];
        $durations = $_POST['tf-durations'] ?? [];
        $expires   = $_POST['tf-expires'] ?? [];

        $rules = [];
        $current_time = time();

        foreach ($keywords as $index => $keyword) {
            $keyword = trim($keyword);
            if (!empty($keyword)) {
                $duration = $durations[$index] ?? 'always';
                $expire_timestamp = intval($expires[$index] ?? 0);

                if ($expire_timestamp == 0 || (isset($b['tf-durations-changed']))) { // Fallback/Sicherheit
                    if ($duration === '1w') {
                        $expire_timestamp = $current_time + (7 * 24 * 60 * 60);
                    } elseif ($duration === '1m') {
                        $expire_timestamp = $current_time + (30 * 24 * 60 * 60);
                    } else {
                        $expire_timestamp = 0; // "Immer"
                    }
                }

                $rules[] = [
                    'keyword'  => $keyword,
                    'type'     => $types[$index] ?? 'hashtag',
                    'duration' => $duration,
                    'expires'  => $expire_timestamp
                ];
            }
        }

        DI::pConfig()->set($uid, 'timelinefilter', 'rules', json_encode($rules));
        DI::pConfig()->set($uid, 'timelinefilter', 'disable', $disable);
    }
}

function timelinefilter_page_end(&$html)
{
    $uid = DI::userSession()->getLocalUserId();
    if (!$uid || empty($html) || DI::pConfig()->get($uid, 'timelinefilter', 'disable')) {
        return;
    }

    $rules_json = DI::pConfig()->get($uid, 'timelinefilter', 'rules', '[]');
    $rules = json_decode($rules_json, true);
    if (empty($rules)) {
        return;
    }

    $hashtag_filters = [];
    $word_filters = [];
    $current_time = time();
    $expired_found = false;

    foreach ($rules as $rule) {
        if (isset($rule['expires']) && $rule['expires'] > 0 && $current_time > $rule['expires']) {
            $expired_found = true;
            continue;
        }

        $word = mb_strtolower($rule['keyword']);
        if ($rule['type'] === 'hashtag') {
            $hashtag_filters[] = ltrim($word, '#');
        } else {
            $word_filters[] = $word;
        }
    }

    if ($expired_found) {
        $clean_rules = array_filter($rules, function($r) use ($current_time) {
            return !(isset($r['expires']) && $r['expires'] > 0 && $current_time > $r['expires']);
        });
        DI::pConfig()->set($uid, 'timelinefilter', 'rules', json_encode(array_values($clean_rules)));
    }

    if (empty($hashtag_filters) && empty($word_filters)) {
        return;
    }

    $script = '
    <script>
    (function() {
        const hashtags = ' . json_encode($hashtag_filters) . ';
        const words = ' . json_encode($word_filters) . ';

        function applyTimelineFilter() {
            const posts = document.querySelectorAll("article, .thread-wrapper, .wall-item-container");

            posts.forEach(post => {
                if (post.style.display === "none") return;

                const postText = post.textContent.toLowerCase();
                let shouldHide = false;

                for (const word of words) {
                    if (postText.includes(word)) {
                        shouldHide = true;
                        break;
                    }
                }

                if (!shouldHide && hashtags.length > 0) {
                    for (const tag of hashtags) {
                        if (postText.includes("#" + tag)) {
                            shouldHide = true;
                            break;
                        }
                    }

                    if (!shouldHide) {
                        const links = post.querySelectorAll("a");
                        for (const link of links) {
                            const href = link.getAttribute("href") || "";
                            if (hashtags.some(tag => href.toLowerCase().includes("tag/" + tag) || href.toLowerCase().includes("tag=" + tag))) {
                                shouldHide = true;
                                break;
                            }
                        }
                    }
                }

                if (shouldHide) {
                    post.style.setProperty("display", "none", "important");
                }
            });
        }

        applyTimelineFilter();
        const observer = new MutationObserver(applyTimelineFilter);
        observer.observe(document.body, { childList: true, subtree: true });
    })();
    </script>
    ';

    $html .= $script;
}
