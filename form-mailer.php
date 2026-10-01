<?php
/**
 * Shared handler for the website forms (contact, demo request).
 * Validates the POSTed fields and emails them via SMTP - nothing is stored in a DB.
 * Always responds with JSON: {"success": bool, "message": string}
 */
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require __DIR__ . '/phpmailer/Exception.php';
require __DIR__ . '/phpmailer/PHPMailer.php';
require __DIR__ . '/phpmailer/SMTP.php';

function respond($ok, $message, $code = 200) {
    http_response_code($code);
    echo json_encode(['success' => $ok, 'message' => $message]);
    exit;
}

/**
 * @param string $title   Used in the email heading and subject, e.g. "Contact"
 * @param array  $fields  key => [label, required, maxLength]; 'email' and 'phone' keys get format checks,
 *                        'message' keeps its line breaks.
 */
function handle_form($title, array $fields) {
    header('Content-Type: application/json; charset=utf-8');

    $failMsg = 'Sorry, your message could not be sent. Please try again later.';
    $okMsg   = 'Thank you! Your message has been sent successfully.';

    if (!isset($_SERVER['REQUEST_METHOD']) || $_SERVER['REQUEST_METHOD'] !== 'POST') {
        respond(false, 'Invalid request.', 405);
    }

    // Honeypot: real users never fill this hidden field
    if (!empty($_POST['website'])) {
        respond(true, $okMsg);
    }

    $configFile = __DIR__ . '/mail-config.php';
    if (!is_readable($configFile)) {
        error_log("$title form: mail-config.php is missing or not readable by PHP");
        respond(false, $failMsg, 500);
    }
    $config = require $configFile;

    $data = [];
    foreach ($fields as $key => list($label, $required, $max)) {
        $v = isset($_POST[$key]) && is_string($_POST[$key]) ? trim($_POST[$key]) : '';
        if ($key !== 'message') {
            $v = str_replace(["\r", "\n"], ' ', $v); // block header injection
        }
        if ($required && $v === '') {
            respond(false, 'Please fill in all required fields.', 422);
        }
        if (strlen($v) > $max) {
            respond(false, "$label is too long.", 422);
        }
        $data[$key] = $v;
    }

    if (!empty($data['email']) && !filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
        respond(false, 'Please enter a valid email address.', 422);
    }
    if (!empty($data['phone']) && !preg_match('/^\+?[0-9\s\-()]{7,20}$/', $data['phone'])) {
        respond(false, 'Please enter a valid phone number.', 422);
    }

    $e = function ($s) { return nl2br(htmlspecialchars($s, ENT_QUOTES, 'UTF-8')); };
    $rows = '';
    $text = '';
    foreach ($fields as $key => list($label)) {
        $rows .= '<tr><td valign="top"><b>' . $e($label) . ':</b></td><td>' . $e($data[$key]) . '</td></tr>';
        $text .= "$label: {$data[$key]}\n";
    }
    $body = '<html><body style="font-family:Arial,sans-serif;font-size:14px;color:#222">'
          . '<h2 style="color:#0038ba">New ' . $e($title) . ' Submission</h2>'
          . '<table cellpadding="6" style="border-collapse:collapse">' . $rows . '</table>'
          . '</body></html>';

    $topic = !empty($data['subject']) ? $data['subject'] : (!empty($data['company']) ? $data['company'] : $data['name']);

    $mail = new PHPMailer(true);
    try {
        $mail->isSMTP();
        $mail->Host       = $config['host'];
        $mail->Port       = $config['port'];
        $mail->SMTPAuth   = true;
        $mail->Username   = $config['username'];
        $mail->Password   = $config['password'];
        $mail->SMTPSecure = $config['secure'] === 'ssl' ? PHPMailer::ENCRYPTION_SMTPS : PHPMailer::ENCRYPTION_STARTTLS;
        $mail->CharSet    = 'UTF-8';

        $mail->setFrom($config['from_email'], $config['from_name']);
        $mail->addAddress($config['to_email']);
        $mail->addReplyTo($data['email'], $data['name']);

        $mail->isHTML(true);
        $mail->Subject = "SELL360 $title: $topic";
        $mail->Body    = $body;
        $mail->AltBody = $text;

        $mail->send();
        respond(true, $okMsg);
    } catch (Exception $ex) {
        error_log("$title form mail error: " . $mail->ErrorInfo);
        respond(false, $failMsg, 500);
    }
}
