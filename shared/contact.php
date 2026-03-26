<?php
// MSC Headhunting – Contact Form Handler
// Works for all three domains (.com, .de, .nl)

header('Content-Type: text/html; charset=UTF-8');

// Only accept POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit('Method not allowed');
}

// Honeypot check (anti-spam)
if (!empty($_POST['website'])) {
    // Bot filled the hidden field — silently redirect
    header('Location: ' . ($_POST['_redirect'] ?? '/'));
    exit;
}

// Sanitise inputs
$name    = htmlspecialchars(strip_tags(trim($_POST['name']    ?? '')));
$email   = filter_var(trim($_POST['email'] ?? ''), FILTER_SANITIZE_EMAIL);
$phone   = htmlspecialchars(strip_tags(trim($_POST['phone']   ?? '')));
$company = htmlspecialchars(strip_tags(trim($_POST['company'] ?? '')));
$message = htmlspecialchars(strip_tags(trim($_POST['message'] ?? '')));
$redirect = htmlspecialchars(strip_tags(trim($_POST['_redirect'] ?? '/')));

// Validate required fields
if ($name === '' || $email === '' || $message === '') {
    http_response_code(400);
    exit('Please fill in all required fields.');
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    http_response_code(400);
    exit('Please provide a valid email address.');
}

// Build email
$to      = 'info@msc-headhunters.com';
$subject = "Website Enquiry from {$name}";

$body  = "New enquiry via the MSC Headhunting website:\n\n";
$body .= "Name:    {$name}\n";
$body .= "Email:   {$email}\n";
$body .= "Phone:   {$phone}\n";
$body .= "Company: {$company}\n\n";
$body .= "Message:\n{$message}\n";

$headers  = "From: noreply@msc-headhunters.com\r\n";
$headers .= "Reply-To: {$email}\r\n";
$headers .= "Content-Type: text/plain; charset=UTF-8\r\n";
$headers .= "X-Mailer: MSC-Headhunting-Contact-Form\r\n";

// Send
$sent = mail($to, $subject, $body, $headers);

if ($sent) {
    header('Location: ' . $redirect . '?sent=1');
} else {
    http_response_code(500);
    exit('Sorry, something went wrong. Please email us directly at info@msc-headhunters.com');
}
