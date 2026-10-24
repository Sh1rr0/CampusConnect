
<?php
require __DIR__ . '/vendor/autoload.php';

use Endroid\QrCode\QrCode;
use Endroid\QrCode\Writer\SvgWriter;

$text = '';
$qrImage = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $text = trim($_POST['text'] ?? '');

    if ($text === '') {
        $error = 'Please enter some text first.';
    } else {
        try {
            $qrCode = QrCode::create($text)
                ->setSize(300)
                ->setMargin(10);

            $writer = new SvgWriter();
            $result = $writer->write($qrCode);

            $qrImage = $result->getDataUri();
        } catch (Throwable $e) {
            $error = 'QR generation failed: ' . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CampusConnect QR Generator</title>

    <style>
        body {
            font-family: Arial, sans-serif;
            background: #f3f4f6;
            margin: 0;
            padding: 40px 16px;
        }

        .container {
            max-width: 500px;
            margin: auto;
            background: white;
            padding: 30px;
            border-radius: 12px;
            box-shadow: 0 4px 15px #00000012;
            text-align: center;
        }

        h1 {
            color: #1d4ed8;
            font-size: 26px;
        }

        p {
            color: #555;
        }

        textarea {
            width: 100%;
            box-sizing: border-box;
            min-height: 100px;
            padding: 12px;
            margin: 12px 0;
            border: 1px solid #ccc;
            border-radius: 6px;
            resize: vertical;
            font: inherit;
        }

        button {
            background: #2563eb;
            color: white;
            border: none;
            padding: 12px 20px;
            border-radius: 6px;
            cursor: pointer;
            font-size: 16px;
        }

        button:hover {
            background: #1d4ed8;
        }

        .qr-result {
            margin-top: 24px;
            overflow-wrap: anywhere;
        }

        .qr-result img {
            width: 300px;
            max-width: 100%;
            height: auto;
        }

        .error {
            color: #dc2626;
        }

        .download {
            display: inline-block;
            margin-top: 12px;
            color: #2563eb;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>CampusConnect</h1>
        <p>Simple QR Code Generator</p>

        <form method="POST">
            <textarea
                name="text"
                placeholder="Enter text, a URL, or an event name..."
                required
            ><?= htmlspecialchars($text, ENT_QUOTES, 'UTF-8') ?></textarea>

            <button type="submit">Generate QR Code</button>
        </form>

        <?php if ($error !== ''): ?>
            <p class="error">
                <?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?>
            </p>
        <?php endif; ?>

        <?php if ($qrImage !== ''): ?>
            <div class="qr-result">
                <h3>Your QR Code</h3>

                <img
                    src="<?= htmlspecialchars($qrImage, ENT_QUOTES, 'UTF-8') ?>"
                    alt="Generated QR code"
                >

                <p>Scan this code with your phone to read its contents.</p>

                <a
                    class="download"
                    href="<?= htmlspecialchars($qrImage, ENT_QUOTES, 'UTF-8') ?>"
                    download="campusconnect-qr.svg"
                >Download QR Code</a>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>
