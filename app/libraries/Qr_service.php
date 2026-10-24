<?php
require_once dirname(__DIR__, 2) . '/vendor/autoload.php';

use Endroid\QrCode\QrCode;
use Endroid\QrCode\Writer\PngWriter;

class Qr_service {
    private static function env($k) { $v = getenv($k); return $v !== false ? $v : ($_ENV[$k] ?? ''); }

    // Deterministic per (event, student): the QR can be re-drawn any time,
    // while the database only stores the SHA-256 hash of the token.
    public static function token($event_id, $student_id) {
        $secret = self::env('QR_SECRET') ?: 'change-this-secret';
        return hash_hmac('sha256', $event_id . ':' . $student_id, $secret);
    }

    public static function token_hash($token) { return hash('sha256', $token); }

    private static function png($data) {
        set_error_handler(function () { return true; }, E_DEPRECATED); // PHP 8.5 vs older endroid
        try { return (new PngWriter())->write(new QrCode(data: $data)); }
        finally { restore_error_handler(); }
    }

    public static function data_uri($data) { return self::png($data)->getDataUri(); }
    public static function bytes($data)    { return self::png($data)->getString(); }
}