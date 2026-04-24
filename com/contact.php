<?php
// MSC Headhunting .com – Contact Form Handler (PHP 8.2)

// --- Per-domain config ---------------------------------------------
$TO_EMAIL   = 'info@msc-headhunters.com';
$FROM_EMAIL = 'noreply@msc-headhunters.com';
$SUCCESS_URL = '/thank-you/';
$ERROR_MESSAGE = 'Sorry, something went wrong. Please email us directly at info@msc-headhunters.com';
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
    exit('Please fill in all required fields.');
}
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    http_response_code(400);
    exit('Please provide a valid email address.');
}

$subject = "Website Enquiry from {$name}";
$body  = "New enquiry via the MSC Headhunting website (.com):\n\n";
$body .= "Name:    {$name}\n";
$body .= "Email:   {$email}\n";
$body .= "Phone:   {$phone}\n";
$body .= "Company: {$company}\n\n";
$body .= "Message:\n{$message}\n";

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
