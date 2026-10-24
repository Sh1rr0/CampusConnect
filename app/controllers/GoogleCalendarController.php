
<?php
defined('PREVENT_DIRECT_ACCESS') OR exit('No direct script access allowed');

class GoogleCalendarController
{
    private function configureGoogle()
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        require_once ROOT_DIR . 'vendor/autoload.php';

        $envFile = ROOT_DIR . '.env';

        if (!is_file($envFile)) {
            throw new RuntimeException('.env file not found.');
        }

        $env = parse_ini_file($envFile, false, INI_SCANNER_RAW);

        if (!$env) {
            throw new RuntimeException('Could not read .env.');
        }

        $client = new Google\Client();
        $client->setClientId(trim($env['GOOGLE_CLIENT_ID'] ?? ''));
        $client->setClientSecret(trim($env['GOOGLE_CLIENT_SECRET'] ?? ''));
        $client->setRedirectUri(trim($env['GOOGLE_REDIRECT_URI'] ?? ''));

        $client->setScopes([
            Google\Service\Calendar::CALENDAR_EVENTS
        ]);

        return $client;
    }

    public function test()
    {
        try {
            $client = $this->configureGoogle();

            $state = bin2hex(random_bytes(32));
            $_SESSION['google_calendar_state'] = $state;

            $client->setState($state);

            header('Location: ' . $client->createAuthUrl());
            exit;
        } catch (Throwable $e) {
            error_log('Google Calendar setup error: ' . $e->getMessage());
            http_response_code(500);
            echo 'Could not start Google authorization. Check your local PHP error log and Google settings.';
        }
    }

    public function callback()
    {
        try {
            $client = $this->configureGoogle();

            if (isset($_GET['error'])) {
                echo 'Google authorization was cancelled or denied.';
                return;
            }

            if (
                empty($_GET['code']) ||
                empty($_GET['state']) ||
                empty($_SESSION['google_calendar_state']) ||
                !hash_equals(
                    $_SESSION['google_calendar_state'],
                    $_GET['state']
                )
            ) {
                http_response_code(400);
                echo 'Invalid or missing OAuth state. Start the test again.';
                return;
            }

            unset($_SESSION['google_calendar_state']);

            $token = $client->fetchAccessTokenWithAuthCode($_GET['code']);

            if (isset($token['error'])) {
                http_response_code(400);
                echo 'Google token exchange failed. Check your OAuth configuration.';
                return;
            }

            $client->setAccessToken($token);

            $calendar = new Google\Service\Calendar($client);

            $event = new Google\Service\Calendar\Event([
                'summary' => 'CampusConnect API Test',
                'description' => 'Test event created by CampusConnect.',
                'start' => [
                    'dateTime' => date(DATE_RFC3339, strtotime('+1 day 10:00')),
                    'timeZone' => 'Asia/Manila'
                ],
                'end' => [
                    'dateTime' => date(DATE_RFC3339, strtotime('+1 day 11:00')),
                    'timeZone' => 'Asia/Manila'
                ]
            ]);

            $created = $calendar->events->insert('primary', $event);

            echo '<h2>Google Calendar connection successful!</h2>';
            echo '<p>Test event created: CampusConnect API Test</p>';
            echo '<p><a href="' .
                htmlspecialchars($created->getHtmlLink(), ENT_QUOTES, 'UTF-8') .
                '">Open the test event in Google Calendar</a></p>';

        } catch (Throwable $e) {
            error_log('Google Calendar callback error: ' . $e->getMessage());
            http_response_code(500);
            echo 'The Google Calendar test failed. Check your PHP error log for details.';
        }
    }
}
