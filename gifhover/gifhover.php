<?php

/**
 * Name: GIF Hover Play
 * Description: Animated GIFs in postings only play when the mouse pointer hovers over the image
 * Version: 1.0
 * Author: Matthias Ebers
 * Maintainer: Matthias Ebers <https://loma.ml>
 * Status: stable
 * License: AGPL-3.0-or-later
 */

use Friendica\Core\Hook;
use Friendica\Database\DBA;
use Friendica\DI;

const GIFHOVER_FREEZE_CLASS = 'gifhover-freeze';
const GIFHOVER_APNG_CLASS   = 'gifhover-apng';

function gifhover_install()
{
	Hook::register('prepare_body_final', __FILE__, 'gifhover_prepare_body_final');
	Hook::register('head', __FILE__, 'gifhover_head');
	Hook::register('footer', __FILE__, 'gifhover_footer');
}

function gifhover_uninstall()
{
}

function gifhover_head()
{
	DI::page()->registerStylesheet(__DIR__ . '/gifhover.css');
}

function gifhover_footer()
{
	DI::page()->registerFooterScript(__DIR__ . '/gifhover.js');
}

/**
 * Marks GIF and PNG images in the rendered post body.
 *
 * @param mixed &$body Hook data array with "html" key, or the HTML string for BC
 */
function gifhover_prepare_body_final(&$body)
{
	if (is_array($body) && isset($body['html'])) {
		gifhover_markImages($body['html']);
	} elseif (is_string($body)) {
		gifhover_markImages($body);
	}
}

function gifhover_markImages(&$html)
{
	if (strpos($html, '<img') === false) {
		return;
	}

	if (!preg_match_all('/<img\b[^>]*>/i', $html, $matches) || empty($matches[0])) {
		return;
	}

	$ids = gifhover_getAnimatedIds($matches[0]);

	foreach (array_unique($matches[0]) as $imageTag) {
		if (gifhover_isEmoji($imageTag)) {
			continue;
		}

		$class = gifhover_getImageClass($imageTag, $ids);
		if (empty($class)) {
			continue;
		}

		$marked = gifhover_addClass($imageTag, $class);
		if ($marked !== $imageTag) {
			$html = str_replace($imageTag, $marked, $html);
		}
	}
}

/**
 * Collects the ids of proxied images and looks up their MIME types:
 * "/photo/link/{id}" -> table "post-link", "/photo/media/{id}" and
 * "/photo/preview/{size}/{id}" -> table "post-media".
 *
 * @param array $imageTags Image tags found in the post body
 *
 * @return array GIF and PNG ids from both tables
 */
function gifhover_getAnimatedIds(array $imageTags)
{
	$linkIds  = [];
	$mediaIds = [];

	foreach ($imageTags as $imageTag) {
		if (preg_match('/\/photo\/link\/(\d+)/', $imageTag, $match)) {
			$linkIds[(int) $match[1]] = true;
		}

		if (preg_match('/\/photo\/(?:media|preview)(?:\/\d+)?\/(\d+)/', $imageTag, $match)) {
			$mediaIds[(int) $match[1]] = true;
		}
	}

	$ids = [
		'gif' => ['link' => [], 'media' => []],
		'png' => ['link' => [], 'media' => []],
	];

	if (!empty($linkIds)) {
		$conditions = DBA::select('post-link', ['id', 'mimetype'], ['id' => array_keys($linkIds)]);
		while ($row = DBA::fetch($conditions)) {
			gifhover_sortId($ids, $row, 'link');
		}
		DBA::close($conditions);
	}

	if (!empty($mediaIds)) {
		$conditions = DBA::select('post-media', ['id', 'mimetype'], ['id' => array_keys($mediaIds)]);
		while ($row = DBA::fetch($conditions)) {
			gifhover_sortId($ids, $row, 'media');
		}
		DBA::close($conditions);
	}

	return $ids;
}

function gifhover_sortId(array &$ids, array $row, string $source)
{
	if (($row['mimetype'] ?? '') === 'image/gif') {
		$ids['gif'][$source][] = (int) $row['id'];
	} elseif (($row['mimetype'] ?? '') === 'image/png') {
		$ids['png'][$source][] = (int) $row['id'];
	}
}

function gifhover_isEmoji(string $imageTag)
{
	return (bool) preg_match('/\balt=":[^"]+:"/', $imageTag);
}

/**
 * Returns the class an image tag has to get, or null.
 *
 * @param string $imageTag
 * @param array  $ids       GIF and PNG ids, see gifhover_getAnimatedIds()
 *
 * @return string|null
 */
function gifhover_getImageClass(string $imageTag, array $ids)
{
	if (preg_match('/\.(?:gif|apng)\b/i', $imageTag)) {
		return GIFHOVER_FREEZE_CLASS;
	}

	if (preg_match('/\/photo\/link\/(\d+)/', $imageTag, $match)) {
		$id = (int) $match[1];
		if (in_array($id, $ids['gif']['link'])) {
			return GIFHOVER_FREEZE_CLASS;
		}
		if (in_array($id, $ids['png']['link'])) {
			return GIFHOVER_APNG_CLASS;
		}
	}

	if (preg_match('/\/photo\/(?:media|preview)(?:\/\d+)?\/(\d+)/', $imageTag, $match)) {
		$id = (int) $match[1];
		if (in_array($id, $ids['gif']['media'])) {
			return GIFHOVER_FREEZE_CLASS;
		}
		if (in_array($id, $ids['png']['media'])) {
			return GIFHOVER_APNG_CLASS;
		}
	}

	return null;
}

function gifhover_addClass(string $imageTag, string $class)
{
	if (strpos($imageTag, $class) !== false) {
		return $imageTag;
	}

	if (preg_match('/\bclass=(["\'])(.*?)\1/i', $imageTag, $match)) {
		return preg_replace_callback(
			'/\bclass=(["\'])(.*?)\1/i',
			function () use ($match, $class) {
				return 'class=' . $match[1] . $match[2] . ' ' . $class . $match[1];
			},
			$imageTag,
			1
		);
	}

	return preg_replace('/<img\b/i', '<img class="' . $class . '"', $imageTag, 1);
}
