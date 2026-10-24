<?php
class Cloudinary_service {
    private static function env($k) { $v = getenv($k); return $v !== false ? $v : ($_ENV[$k] ?? ''); }

    // Signed upload over plain HTTPS. Returns the image URL, or null on failure.
    public static function upload($tmpPath, $folder = 'campusconnect/posters') {
        $cloud  = self::env('CLOUDINARY_CLOUD_NAME');
        $key    = self::env('CLOUDINARY_API_KEY');
        $secret = self::env('CLOUDINARY_API_SECRET');
        $ts     = time();
        $sig    = sha1('folder=' . $folder . '&timestamp=' . $ts . $secret);

        $ch = curl_init("https://api.cloudinary.com/v1_1/{$cloud}/image/upload");
        curl_setopt_array($ch, array(
            CURLOPT_POST           => true,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => 30,
            CURLOPT_POSTFIELDS     => array(
                'file' => new CURLFile($tmpPath), 'api_key' => $key,
                'timestamp' => $ts, 'folder' => $folder, 'signature' => $sig
            )
        ));
        $res = curl_exec($ch);
      
        $json = $res ? json_decode($res, true) : null;
        return $json['secure_url'] ?? null;
    }
}