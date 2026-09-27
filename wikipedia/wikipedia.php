<?php

/**
 * Name: Wikipedia Link
 * Description: Replaces [wiki]terms[/wiki] with Wikipedia links and provides autocomplete suggestions.
 * Version: 1.3
 * Author: Matthias Ebers <https://loma.ml/profile/feb>
 */

use Friendica\Core\Hook;

function wikipedia_install(): void
{
    Hook::register('post_local', 'addon/wikipedia/wikipedia.php', 'wikipedia_post_local');
    Hook::register('bbcode', 'addon/wikipedia/wikipedia.php', 'wikipedia_bbcode');
    Hook::register('prepare_body', 'addon/wikipedia/wikipedia.php', 'wikipedia_prepare_body');
    Hook::register('page_end', 'addon/wikipedia/wikipedia.php', 'wikipedia_page_end');
}

function wikipedia_uninstall(): void
{
    Hook::unregister('post_local', 'addon/wikipedia/wikipedia.php', 'wikipedia_post_local');
    Hook::unregister('bbcode', 'addon/wikipedia/wikipedia.php', 'wikipedia_bbcode');
    Hook::unregister('prepare_body', 'addon/wikipedia/wikipedia.php', 'wikipedia_prepare_body');
    Hook::unregister('page_end', 'addon/wikipedia/wikipedia.php', 'wikipedia_page_end');
}

function wikipedia_parse_tag(string $text, bool $toHtml = false): string
{
    if (strpos($text, '[wiki') === false) {
        return $text;
    }

    $pattern = '/\[wiki(?:=([a-z]{2,3}))?\](.*?)\[\/wiki\]/is';

    return preg_replace_callback($pattern, function (array $matches) use ($toHtml): string {
        $lang = !empty($matches[1]) ? strtolower($matches[1]) : 'de';
        $term = trim(strip_tags($matches[2]));

        if ($term === '') {
            return $matches[0];
        }

        $wikiPath = rawurlencode(str_replace(' ', '_', $term));
        $url = sprintf('https://%s.wikipedia.org/wiki/%s', $lang, $wikiPath);

        if ($toHtml) {
            return sprintf(
                '<a href="%s" target="_blank" rel="noopener noreferrer">%s</a>',
                $url,
                htmlspecialchars($term, ENT_QUOTES, 'UTF-8')
            );
        }

        return sprintf('[url=%s]%s[/url]', $url, $term);
    }, $text);
}

function wikipedia_post_local(array &$data): void
{
    if (!empty($data['body'])) {
        $data['body'] = wikipedia_parse_tag($data['body'], false);
    }
}

function wikipedia_bbcode(string &$text): void
{
    $text = wikipedia_parse_tag($text, false);
}

function wikipedia_prepare_body(array &$data): void
{
    if (empty($data['html'])) {
        return;
    }

    $data['html'] = wikipedia_parse_tag($data['html'], true);
}

function wikipedia_page_end(string &$html): void
{
    $base = function_exists('baseUrl') ? baseUrl() : '';

    $cssUrl = rtrim($base, '/') . '/addon/wikipedia/wikipedia.css';
    $jsUrl  = rtrim($base, '/') . '/addon/wikipedia/wikipedia.js';

    $html .= '<link rel="stylesheet" type="text/css" href="' . $cssUrl . '">' . "\n";
    $html .= '<script src="' . $jsUrl . '" defer></script>' . "\n";
}
