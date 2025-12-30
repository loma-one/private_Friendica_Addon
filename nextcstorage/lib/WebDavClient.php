<?php

class WebDavClient
{
    private $base_url;
    private $auth;

    public function __construct($url, $user, $pass)
    {
        $this->base_url = rtrim($url, '/') . '/';
        $this->auth = base64_encode("$user:$pass");
    }

    /**
     * Erstellt ein Verzeichnis mittels MKCOL
     */
    public function createFolder($path)
    {
        $folder = trim($path, '/');
        if (empty($folder)) return true;

        $url = $this->base_url . rawurlencode($folder);
        $opts = [
            "http" => [
                "method" => "MKCOL",
                "header" => "Authorization: Basic " . $this->auth . "\r\n"
            ]
        ];
        $ctx = stream_context_create($opts);
        return @file_get_contents($url, false, $ctx) !== false;
    }

    /**
     * Lädt eine Datei mittels PUT hoch
     */
    public function upload($filename, $data, $path = '')
    {
        $url = $this->base_url;
        if (!empty($path)) {
            $url .= trim($path, '/') . '/';
        }
        $url .= rawurlencode($filename);

        $opts = [
            "http" => [
                "method" => "PUT",
                "header" => "Authorization: Basic " . $this->auth . "\r\n" .
                            "Content-Type: application/octet-stream\r\n",
                "content" => $data
            ]
        ];
        $ctx = stream_context_create($opts);
        return @file_get_contents($url, false, $ctx);
    }
}
