<?php
/**
 * Name: Tesseract OCR
 * Description: Use OCR to get text from images (with timeout, resource limits, format check)
 * Version: 0.2
 * Author: Michael Vogel <http://pirati.ca/profile/heluecht>
 * Modified by: Matthias Ebers <http://loma.ml/profile/feb>
 */

use Friendica\Core\Hook;
use Friendica\Core\Logger;
use Friendica\Core\System;
use thiagoalessio\TesseractOCR\TesseractOCR;

require_once __DIR__ . DIRECTORY_SEPARATOR . 'vendor' . DIRECTORY_SEPARATOR . 'autoload.php';

/**
 * Is called up when the add-on is activated
 */
function tesseract_install()
{
	Hook::register('ocr-detection', __FILE__, 'tesseract_ocr_detection');

	$wrapperPath = __DIR__ . '/tesseract-limited.sh';

	// Create wrapper script with timeout and CPU/I/O priority
	if (!file_exists($wrapperPath)) {
		$script = <<<BASH
#!/bin/bash
# Wrapper for tesseract with timeout and resource limits
timeout 5s nice -n 10 ionice -c3 /usr/bin/tesseract "\$@"
BASH;

		file_put_contents($wrapperPath, $script);
		chmod($wrapperPath, 0755);

		Logger::notice('Tesseract wrapper script created', ['path' => $wrapperPath]);
	} else {
		Logger::info('Tesseract wrapper script already exists', ['path' => $wrapperPath]);
	}

	Logger::notice('Tesseract OCR addon installed');
}

/**
 * Is called up when the add-on is deactivated
 */
function tesseract_uninstall()
{
	$wrapperPath = __DIR__ . '/tesseract-limited.sh';

	if (file_exists($wrapperPath)) {
		unlink($wrapperPath);
		Logger::notice('Tesseract wrapper script removed', ['path' => $wrapperPath]);
	}

	Hook::unregister('ocr-detection', __FILE__, 'tesseract_ocr_detection');
	Logger::notice('Tesseract OCR addon uninstalled');
}

/**
 * Main function for OCR recognition
 */
function tesseract_ocr_detection(&$media)
{
	// ➤ Alt text available? → Skip OCR
	if (!empty($media['description'])) {
		Logger::debug('Image already has description, skipping OCR');
		return;
	}

	// Format check: Only process certain image types
	$allowedTypes = ['image/jpeg', 'image/png', 'image/bmp', 'image/tiff'];
	if (!empty($media['type']) && !in_array($media['type'], $allowedTypes)) {
		Logger::debug('Unsupported image type for OCR', ['type' => $media['type']]);
		return;
	}

	// Alternatively: Check file extension (if MIME type is missing)
	if (empty($media['type']) && !empty($media['filename']) && preg_match('/\.gif$/i', $media['filename'])) {
		Logger::debug('GIF image detected via filename, skipping OCR');
		return;
	}

	$ocr = new TesseractOCR();

	try {
		// Bash wrapper with resource limit
		$wrapperPath = __DIR__ . '/tesseract-limited.sh';
		$ocr->executable($wrapperPath);

		// Load all available languages
		$languages = $ocr->availableLanguages();
		if ($languages) {
			$ocr->lang(implode('+', $languages));
		}

		// Set temporary directory
		$ocr->tempDir(System::getTempPath());

		// Set image data
		$ocr->imageData($media['img_str'], strlen($media['img_str']));

		// Start OCR
		$text = trim($ocr->run());

		if (!empty($text)) {
			$media['description'] = $text;
			Logger::debug('OCR text detected', ['text' => $text]);
		} else {
			Logger::debug('No text detected in image');
		}
	} catch (\Throwable $th) {
		Logger::info('Error calling TesseractOCR', ['message' => $th->getMessage()]);
	}
}
