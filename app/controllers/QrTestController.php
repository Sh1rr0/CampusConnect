<?php
defined('PREVENT_DIRECT_ACCESS') OR exit('No direct script access allowed');

use Endroid\QrCode\QrCode;
use Endroid\QrCode\Writer\PngWriter;

class QrTestController extends Controller {
    public function index() {
        require_once dirname(__DIR__, 2) . '/vendor/autoload.php';

        $token  = bin2hex(random_bytes(16));   // same kind of token used for registrations
        $qrCode = new QrCode(data: $token);
        $result = (new PngWriter())->write($qrCode);

        echo '<h2>QR Test</h2>';
        echo '<p>Token: ' . htmlspecialchars($token) . '</p>';
        echo '<img src="' . $result->getDataUri() . '" alt="QR code">';
    }
}