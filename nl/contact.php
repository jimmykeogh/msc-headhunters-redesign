<?php
// MSC Headhunting .nl – Contact Form Handler (PHP 8.2)

// --- Per-domain config ---------------------------------------------
$TO_EMAIL   = 'info@msc-headhunters.nl';
$FROM_EMAIL = 'noreply@msc-headhunters.nl';
$SUCCESS_URL = '/thank-you.html';
$ERROR_MESSAGE = 'Sorry, er is iets misgegaan. Stuur ons direct een e-mail naar info@msc-headhunters.nl';
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
    exit('Vul alstublieft alle verplichte velden in.');
}
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    http_response_code(400);
    exit('Geef een geldig e-mailadres op.');
}

$subject = "Aanvraag via website van {$name}";
$body  = "Nieuwe aanvraag via de MSC Headhunting website (.nl):\n\n";
$body .= "Naam:      {$name}\n";
$body .= "E-mail:    {$email}\n";
$body .= "Telefoon:  {$phone}\n";
$body .= "Bedrijf:   {$company}\n\n";
$body .= "Bericht:\n{$message}\n";

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
