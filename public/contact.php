<?php
declare(strict_types=1);
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

function clean(string $val): string {
    return htmlspecialchars(strip_tags(trim($val)), ENT_QUOTES, 'UTF-8');
}

$name    = clean($_POST['name']    ?? '');
$email   = clean($_POST['email']   ?? '');
$phone   = clean($_POST['phone']   ?? '');
$service = clean($_POST['service'] ?? '');
$message = clean($_POST['message'] ?? '');
$honey   = $_POST['website']       ?? '';

// Honeypot trap
if (!empty($honey)) {
    echo json_encode(['success' => true]);
    exit;
}

// Validate
$errors = [];
if (strlen($name) < 2)                             $errors[] = 'Please enter your full name.';
if (!filter_var($email, FILTER_VALIDATE_EMAIL))    $errors[] = 'Please enter a valid email address.';
if (strlen($message) < 10)                         $errors[] = 'Message must be at least 10 characters.';

if (!empty($errors)) {
    echo json_encode(['success' => false, 'message' => implode(' ', $errors)]);
    exit;
}

$to      = 'info@ambozygroup.com';
$subject = '=?UTF-8?B?' . base64_encode("Website Enquiry: {$service} — {$name}") . '?=';

$body  = "New enquiry received via ambozygraphics.shop\n";
$body .= str_repeat('=', 50) . "\n\n";
$body .= "Name    : {$name}\n";
$body .= "Email   : {$email}\n";
$body .= "Phone   : {$phone}\n";
$body .= "Service : {$service}\n\n";
$body .= "Message :\n{$message}\n\n";
$body .= str_repeat('-', 50) . "\n";
$body .= "Sent at : " . date('d M Y, H:i:s') . " UTC\n";

$headers  = "MIME-Version: 1.0\r\n";
$headers .= "Content-Type: text/plain; charset=UTF-8\r\n";
$headers .= "From: =?UTF-8?B?" . base64_encode("Ambozy Website") . "?= <noreply@ambozygraphics.shop>\r\n";
$headers .= "Reply-To: {$email}\r\n";

$sent = @mail($to, $subject, $body, $headers);

// Auto-reply to client
if (!empty($email)) {
    $autoSubject = '=?UTF-8?B?' . base64_encode("Thank you — Ambozy Graphics Solutions Ltd") . '?=';
    $autoBody    = "Dear {$name},\n\n";
    $autoBody   .= "Thank you for contacting Ambozy Graphics Solutions Ltd.\n";
    $autoBody   .= "We have received your enquiry and will respond within 24 hours.\n\n";
    if ($service) $autoBody .= "Service of interest: {$service}\n\n";
    $autoBody   .= "For urgent matters, reach us directly:\n";
    $autoBody   .= "  Office : +256-392-839-447\n";
    $autoBody   .= "  Mobile : +256-782-187-799 / +256-702-371-230\n";
    $autoBody   .= "  WhatsApp: +256-782-187-799\n\n";
    $autoBody   .= "Warm regards,\n";
    $autoBody   .= "Ambozy Graphics Solutions Ltd\n";
    $autoBody   .= "Plot 43 Nasser/Nkrumah Road, Kampala, Uganda\n";
    $autoBody   .= "ambozygraphics.shop\n";

    $autoHeaders  = "MIME-Version: 1.0\r\n";
    $autoHeaders .= "Content-Type: text/plain; charset=UTF-8\r\n";
    $autoHeaders .= "From: =?UTF-8?B?" . base64_encode("Ambozy Graphics Solutions") . "?= <info@ambozygroup.com>\r\n";
    @mail($email, $autoSubject, $autoBody, $autoHeaders);
}

echo json_encode([
    'success' => true,
    'message' => "Thank you {$name}! Your message has been received. We'll be in touch within 24 hours."
]);
