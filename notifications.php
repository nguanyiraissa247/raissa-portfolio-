<?php
declare(strict_types=1);

require_once __DIR__ . '/config.php';

function notify_admin_of_enquiry(array $enquiry): bool
{
    if (ADMIN_EMAIL === '' || !filter_var(ADMIN_EMAIL, FILTER_VALIDATE_EMAIL)) {
        return false;
    }

    $subject = 'New portfolio enquiry: ' . (string) $enquiry['service'];
    $body = "A new enquiry was submitted.\n\n"
        . 'Name: ' . (string) $enquiry['name'] . "\n"
        . 'Email: ' . (string) $enquiry['email'] . "\n"
        . 'Service: ' . (string) $enquiry['service'] . "\n\n"
        . "Message:\n" . (string) $enquiry['message'] . "\n";
    $headers = 'From: ' . MAIL_FROM . "\r\n"
        . 'Reply-To: ' . (string) $enquiry['email'] . "\r\n"
        . 'Content-Type: text/plain; charset=UTF-8';

    return mail(ADMIN_EMAIL, $subject, $body, $headers);
}