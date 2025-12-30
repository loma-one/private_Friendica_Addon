<?php

class OCMClient
{
    private $cloud_id;

    public function __construct($id)
    {
        $this->cloud_id = $id;
    }

    public function discover()
    {
        $parts = explode('@', $this->cloud_id);
        if (count($parts) < 2) return false;

        $user = $parts[0];
        $domain = $parts[1];

        // Standard-Pfad als Fallback
        $webdav_url = "https://" . $domain . "/remote.php/dav/files/" . $user . "/";

        // Discovery via OCM versuchen
        $url = "https://" . $domain . "/.well-known/ocm";
        $opts = [
            "http" => ["method" => "GET", "timeout" => 3, "ignore_errors" => true],
            "ssl"  => ["verify_peer" => false, "verify_peer_name" => false]
        ];

        $ctx = stream_context_create($opts);
        $res = @file_get_contents($url, false, $ctx);

        if ($res) {
            $data = json_decode($res, true);
            if (!empty($data['endpoints']['webdav'])) {
                $webdav_url = $data['endpoints']['webdav'];
            }
        }

        return ['webdav' => $webdav_url];
    }
}
