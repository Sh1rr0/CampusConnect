
<?php
session_start();

require_once dirname(__DIR__) . '/vendor/autoload.php';

$envFile = dirname(__DIR__) . '/.env';

if (!is_file($envFile)) {
    exit('.env file not found.');
}

$env = parse_ini_file($envFile, false, INI_SCANNER_RAW);

if (!$env) {
    exit('Could not read .env.');
}

$clientId = trim($env['GOOGLE_CLIENT_ID'] ?? '');
$clientSecret = trim($env['GOOGLE_CLIENT_SECRET'] ?? '');
$redirectUri = trim($env['GOOGLE_REDIRECT_URI'] ?? '');

if (!$clientId || !$clientSecret || !$redirectUri) {
    exit('Missing Google OAuth settings in .env.');
}

$client = new Google\Client();
$client->setClientId($clientId);
$client->setClientSecret($clientSecret);
$client->setRedirectUri($redirectUri);
$client->setScopes([
    Google\Service\Calendar::CALENDAR_EVENTS
]);

$client->setAccessType('offline');

if (isset($_GET['error'])) {
    exit('Google authorization was cancelled or denied: ' .
        htmlspecialchars($_GET['error'], ENT_QUOTES, 'UTF-8'));
}

if (isset($_GET['code'])) {
    if (
        empty($_GET['state']) ||
        empty($_SESSION['google_oauth_state']) ||
        !hash_equals($_SESSION['google_oauth_state'], $_GET['state'])
    ) {
        http_response_code(400);
        exit('Invalid OAuth state. Please restart the test.');
    }

    unset($_SESSION['google_oauth_state']);

    $token = $client->fetchAccessTokenWithAuthCode($_GET['code']);

    if (isset($token['error'])) {
        exit('Token exchange failed: ' .
            htmlspecialchars($token['error_description'] ??
                $token['error'], ENT_QUOTES, 'UTF-8'));
    }

    $client->setAccessToken($token);

    $calendar = new Google\Service\Calendar($client);

    $event = new Google\Service\Calendar\Event([
        'summary' => 'CampusConnect API Test',
        'description' => 'Test event created by CampusConnect.',
        'start' => [
            'dateTime' => date(
                DATE_RFC3339,
                strtotime('+1 day 10:00')
            ),
            'timeZone' => 'Asia/Manila'
        ],
        'end' => [
            'dateTime' => date(
                DATE_RFC3339,
                strtotime('+1 day 11:00')
            ),
            'timeZone' => 'Asia/Manila'
        ]
    ]);

    try {
        $created = $calendar->events->insert(
            'primary',
            $event
        );

        echo '<h2>Google Calendar connection successful!</h2>';
        echo '<p>Test event: CampusConnect API Test</p>';
        echo '<p>Event ID: ' .
            htmlspecialchars($created->getId(), ENT_QUOTES, 'UTF-8') .
            '</p>';
        echo '<p><a href="' .
            htmlspecialchars($created->getHtmlLink(), ENT_QUOTES, 'UTF-8') .
            '" target="_blank" rel="noopener">Open test event in Google Calendar</a></p>';
    } catch (Throwable $e) {
        http_response_code(500);
        echo 'Could not create event. Check the Google API settings and logs.';
        error_log('CampusConnect Calendar test failed: ' . $e->getMessage());
    }

    exit;
}

$state = bin2hex(random_bytes(32));
$_SESSION['google_oauth_state'] = $state;
$client->setState($state);

header('Location: ' . $client->createAuthUrl());
exit;
