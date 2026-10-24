<?php
class Brevo_service {
    public static $last_error = '';
    private static function env($k) { $v = getenv($k); return $v !== false ? $v : ($_ENV[$k] ?? ''); }

    // $attachments: array(array('name' => 'x.png', 'content' => base64string))
    public static function send($toEmail, $toName, $subject, $html, $attachments = array()) {
        $payload = array(
            'sender'      => array('name' => self::env('BREVO_SENDER_NAME') ?: 'CampusConnect',
                                   'email' => self::env('BREVO_SENDER_EMAIL')),
            'to'          => array(array('email' => $toEmail, 'name' => $toName)),
            'subject'     => $subject,
            'htmlContent' => $html
        );
        if ($attachments) $payload['attachment'] = $attachments;

        $ch = curl_init('https://api.brevo.com/v3/smtp/email');
        curl_setopt_array($ch, array(
            CURLOPT_POST           => true,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => 15,
            CURLOPT_HTTPHEADER     => array('accept: application/json', 'content-type: application/json',
                                            'api-key: ' . self::env('BREVO_API_KEY')),
            CURLOPT_POSTFIELDS     => json_encode($payload)
        ));
        $res  = curl_exec($ch);
        $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        self::$last_error = $res === false ? curl_error($ch) : $res;
        
        return $code >= 200 && $code < 300;
    }
}