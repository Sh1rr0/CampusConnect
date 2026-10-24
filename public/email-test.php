
<?php

$envFile = dirname(__DIR__) . '/.env';

if (!is_file($envFile)) {
    exit('Environment file not found.');
}

$env = parse_ini_file($envFile, false, INI_SCANNER_RAW);

if ($env === false) {
    exit('Could not read .env. Check its formatting.');
}

$apiKey = trim($env['BREVO_API_KEY'] ?? '');
$senderEmail = trim($env['BREVO_SENDER_EMAIL'] ?? '');

// CHANGE THIS to the email address that should receive the test.
$recipientEmail = 'johnerisdg@gmail.com';

if ($apiKey === '' || $senderEmail === '') {
    exit('Missing Brevo API key or sender email in .env.');
}

if (!filter_var($senderEmail, FILTER_VALIDATE_EMAIL)) {
    exit('The sender email in .env is invalid.');
}

if (!filter_var($recipientEmail, FILTER_VALIDATE_EMAIL)) {
    exit('Please enter a valid recipient email address in the PHP file.');
}

if (!function_exists('curl_init')) {
    exit('PHP cURL is not enabled. Enable the curl extension in Laragon PHP.');
}

$payload = [
    'sender' => [
        'name' => 'CampusConnect',
        'email' => $senderEmail
    ],
    'to' => [
        [
            'email' => $recipientEmail
        ]
    ],
    'subject' => 'CampusConnect Email API Test',
    'textContent' =>
        'Hello! This is a test email from CampusConnect. ' .
        'Your email API integration is working.'
];

$ch = curl_init('https://api.brevo.com/v3/smtp/email');

curl_setopt_array($ch, [
    CURLOPT_POST => true,
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_HTTPHEADER => [
        'accept: application/json',
        'api-key: ' . $apiKey,
        'content-type: application/json'
    ],
    CURLOPT_POSTFIELDS => json_encode($payload),
    CURLOPT_CONNECTTIMEOUT => 10,
    CURLOPT_TIMEOUT => 30
]);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$curlError = curl_error($ch);

// No curl_close() needed for your PHP runtime.

if ($response === false) {
    exit('Connection failed: ' .
        htmlspecialchars($curlError, ENT_QUOTES, 'UTF-8'));
}

if ($httpCode >= 200 && $httpCode < 300) {
    echo '<h2>Email request accepted by Brevo!</h2>';
    echo '<p>Check your inbox and spam folder.</p>';
    echo '<pre>' .
        htmlspecialchars($response, ENT_QUOTES, 'UTF-8') .
        '</pre>';
} else {
    echo '<h2>Email request failed. HTTP status: ' .
        (int) $httpCode . '</h2>';
    echo '<pre>' .
        htmlspecialchars($response, ENT_QUOTES, 'UTF-8') .
        '</pre>';
}
