<?php

/**
 * Name: Wikipedia Link
 * Description: Replaces [wiki]terms[/wiki] with Wikipedia links.
 * Version: 1.0
 * Author: Matthias Ebers <https://loma.ml/profile/feb>
 */

use Friendica\Core\Hook;

function wikipedia_install(): void
{
    Hook::register('prepare_body', 'addon/wikipedia/wikipedia.php', 'wikipedia_prepare_body');
    Hook::register('page_end', 'addon/wikipedia/wikipedia.php', 'wikipedia_page_end');
}

function wikipedia_uninstall(): void
{
    Hook::unregister('prepare_body', 'addon/wikipedia/wikipedia.php', 'wikipedia_prepare_body');
    Hook::unregister('page_end', 'addon/wikipedia/wikipedia.php', 'wikipedia_page_end');
}

function wikipedia_prepare_body(array &$item): void
{
    if (empty($item['html'])) {
        return;
    }

    $pattern = '/\[wiki(?:=([a-z]{2,3}))?\](.*?)\[\/wiki\]/is';

    $item['html'] = preg_replace_callback($pattern, function (array $matches): string {
        $lang = !empty($matches[1]) ? strtolower($matches[1]) : 'de';

        $term = trim(strip_tags($matches[2]));
        if ($term === '') {
            return $matches[0];
        }

        $wiki_path = rawurlencode(str_replace(' ', '_', $term));
        $url = sprintf('https://%s.wikipedia.org/wiki/%s', $lang, $wiki_path);

        $icon = '<svg xmlns="http://www.w3.org/2000/svg" width="1em" height="1em" viewBox="0 0 24 24" fill="currentColor" style="display:inline-block; vertical-align:-0.125em; margin-right:0.2em; opacity:0.85;"><path d="M12.09 13.118l-2.013-4.64 1.835-4.183h1.895V3h-5.692v1.295h1.341l-1.077 2.451-1.196-2.451h1.378V3H3v1.295h1.492l3.418 7.37-1.57 3.515-2.222-4.886H5.45V9H1v1.295h1.164l3.528 7.734h1.488l2.253-5.011 2.225 5.011h1.489l4.802-10.439h1.38V3h-5.228v1.295h1.341l-3.342 7.523z"/></svg>';

        return sprintf(
            '<a href="%s" target="_blank" rel="noopener noreferrer" class="wiki-link">%s%s</a>',
            $url,
            $icon,
            htmlspecialchars($term, ENT_QUOTES, 'UTF-8')
        );
    }, $item['html']);
}

function wikipedia_page_end(&$html): void
{
    $html .= <<<'JS'
    <script>
    (() => {
        'use strict';

        document.addEventListener('input', (event) => {
            const el = event.target;

            if (!el.matches('#profile-jot-text, textarea[name="body"], .comment-box textarea')) {
                return;
            }

            if (document.querySelector('.textcomplete-dropdown:not([style*="display: none"]), .tribute-container:not([style*="display: none"])')) {
                return;
            }

            const pos = el.selectionStart;
            const val = el.value;

            if (pos >= 2 && val.substring(pos - 2, pos).toLowerCase() === '[w') {
                if (val.substring(pos, pos + 11) === 'iki][/wiki]') {
                    return;
                }

                el.setSelectionRange(pos - 1, pos);

                const success = document.execCommand('insertText', false, 'wiki][/wiki]');
                if (!success && typeof el.setRangeText === 'function') {
                    el.setRangeText('wiki][/wiki]', pos - 1, pos, 'end');
                }

                const targetPos = pos + 4;
                el.setSelectionRange(targetPos, targetPos);
            }
        });
    })();
    </script>
    JS;
}
