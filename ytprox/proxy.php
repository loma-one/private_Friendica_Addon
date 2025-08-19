<?php
// addon/ytprox/proxy.php
// Kleiner Proxy mit Cache für YouTube Thumbnails und oEmbed-Infos

$cacheDir = __DIR__ . '/cache';
if (!is_dir($cacheDir)) {
    mkdir($cacheDir, 0755, true);
}

$type = $_GET['type'] ?? '';
$vid  = preg_replace('/[^a-zA-Z0-9_-]/', '', $_GET['vid'] ?? '');

if (!$vid) {
    header("HTTP/1.1 400 Bad Request");
    echo "Missing video ID";
    exit;
}

if ($type === 'thumb') {
    $file  = "$cacheDir/{$vid}_thumb.jpg";
    $src   = "https://img.youtube.com/vi/$vid/hqdefault.jpg";
    $ctype = "image/jpeg";

} elseif ($type === 'info') {
    $file  = "$cacheDir/{$vid}_info.json";
    $src   = "https://www.youtube.com/oembed?url=https://www.youtube.com/watch?v=$vid&format=json";
    $ctype = "application/json";

} else {
    header("HTTP/1.1 400 Bad Request");
    echo "Invalid type";
    exit;
}

// Cache gültig 24h
if (file_exists($file) && (time() - filemtime($file) < 86400)) {
    header("Content-Type: $ctype");
    readfile($file);
    exit;
}

// Abrufen
$data = @file_get_contents($src);
if ($data === false) {
    header("HTTP/1.1 502 Bad Gateway");
    echo "Failed to fetch resource";
    exit;
}

// Speichern + ausgeben
file_put_contents($file, $data);
header("Content-Type: $ctype");
echo $data;
