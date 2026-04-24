<?php
// MSC Headhunting .de – Contact Form Handler (PHP 8.2)

// --- Per-domain config ---------------------------------------------
$TO_EMAIL   = 'info@msc-headhunters.de';
$FROM_EMAIL = 'noreply@msc-headhunters.de';
$SUCCESS_URL = '/thank-you/';
$ERROR_MESSAGE = 'Entschuldigung, es ist ein Fehler aufgetreten. Bitte senden Sie uns direkt eine E-Mail an info@msc-headhunters.de';
// -------------------------------------------------------------------

header('Content-Type: text/html; charset=UTF-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit('Method not allowed');
}

// Honeypot — silently drop bots
if (!empty($_POST['website'])) {
    header('Location: ' . $SUCCESS_URL);
    exit;
}

$name    = htmlspecialchars(strip_tags(trim($_POST['name']    ?? '')));
$email   = filter_var(trim($_POST['email'] ?? ''), FILTER_SANITIZE_EMAIL);
$phone   = htmlspecialchars(strip_tags(trim($_POST['phone']   ?? '')));
$company = htmlspecialchars(strip_tags(trim($_POST['company'] ?? '')));
$message = htmlspecialchars(strip_tags(trim($_POST['message'] ?? '')));

if ($name === '' || $email === '' || $message === '') {
    http_response_code(400);
    exit('Bitte füllen Sie alle Pflichtfelder aus.');
}
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    http_response_code(400);
    exit('Bitte geben Sie eine gültige E-Mail-Adresse an.');
}

$subject = "Webseiten-Anfrage von {$name}";
$body  = "Neue Anfrage über die MSC Headhunting Webseite (.de):\n\n";
$body .= "Name:         {$name}\n";
$body .= "E-Mail:       {$email}\n";
$body .= "Telefon:      {$phone}\n";
$body .= "Unternehmen:  {$company}\n\n";
$body .= "Nachricht:\n{$message}\n";

$headers  = "From: {$FROM_EMAIL}\r\n";
$headers .= "Reply-To: {$email}\r\n";
$headers .= "Content-Type: text/plain; charset=UTF-8\r\n";
$headers .= "X-Mailer: MSC-Headhunting-Contact-Form\r\n";

if (mail($TO_EMAIL, $subject, $body, $headers)) {
    header('Location: ' . $SUCCESS_URL);
} else {
    http_response_code(500);
    exit($ERROR_MESSAGE);
}
