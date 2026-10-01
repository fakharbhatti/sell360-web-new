<?php
/**
 * Contact form handler - sends the submission by email via SMTP (nothing stored in DB).
 * Called via AJAX from contact.html, returns JSON.
 */
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require __DIR__ . '/phpmailer/Exception.php';
require __DIR__ . '/phpmailer/PHPMailer.php';
require __DIR__ . '/phpmailer/SMTP.php';

header('Content-Type: application/json; charset=utf-8');

$config = require __DIR__ . '/mail-config.php';

function respond($ok, $message, $code = 200) {
    http_response_code($code);
    echo json_encode(['success' => $ok, 'message' => $message]);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    respond(false, 'Invalid request.', 405);
}

// Honeypot: real users never fill this hidden field
if (!empty($_POST['website'])) {
    respond(true, 'Thank you! Your message has been sent successfully.');
}

$clean = function ($key) {
    $v = isset($_POST[$key]) ? trim($_POST[$key]) : '';
    return str_replace(["\r", "\n"], ' ', $v);
};

$name    = $clean('name');
$email   = $clean('email');
$phone   = $clean('phone');
$subject = $clean('subject');
$message = isset($_POST['message']) ? trim($_POST['message']) : '';

if ($name === '' || $email === '' || $message === '') {
    respond(false, 'Please fill in your name, email and message.', 422);
}
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    respond(false, 'Please enter a valid email address.', 422);
}
if (strlen($message) > 5000 || strlen($name) > 100 || strlen($subject) > 200) {
    respond(false, 'Your message is too long.', 422);
}

$e = function ($s) { return nl2br(htmlspecialchars($s, ENT_QUOTES, 'UTF-8')); };

$body = '<html><body style="font-family:Arial,sans-serif;font-size:14px;color:#222">'
      . '<h2 style="color:#0038ba">New Contact Form Submission</h2>'
      . '<table cellpadding="6" style="border-collapse:collapse">'
      . '<tr><td><b>Name:</b></td><td>' . $e($name) . '</td></tr>'
      . '<tr><td><b>Email:</b></td><td>' . $e($email) . '</td></tr>'
      . '<tr><td><b>Phone:</b></td><td>' . $e($phone) . '</td></tr>'
      . '<tr><td><b>Subject:</b></td><td>' . $e($subject) . '</td></tr>'
      . '<tr><td valign="top"><b>Message:</b></td><td>' . $e($message) . '</td></tr>'
      . '</table></body></html>';

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
    $mail->addReplyTo($email, $name);

    $mail->isHTML(true);
    $mail->Subject = 'SELL360 Contact: ' . ($subject !== '' ? $subject : 'New enquiry');
    $mail->Body    = $body;
    $mail->AltBody = "Name: $name\nEmail: $email\nPhone: $phone\nSubject: $subject\n\nMessage:\n$message";

    $mail->send();
    respond(true, 'Thank you! Your message has been sent successfully.');
} catch (Exception $ex) {
    error_log('Contact form mail error: ' . $mail->ErrorInfo);
    respond(false, 'Sorry, your message could not be sent. Please try again later.', 500);
}
