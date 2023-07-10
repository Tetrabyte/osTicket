#!/usr/bin/php -q
<?php

include_once 'include/api-config.php';

$config = [
    'url' => 'https://tickets.remoteit.co.uk/api/http.php/tickets.json',
];

$name = $_GET['name'] ?? null;
$email = $_GET['email'] ?? null;
$phone = $_GET['phone'] ?? null;
$subject = $_GET['subject'] ?? null;
$message = $_GET['message'] ?? null;
$notes = $_GET['note'] ?? null;

$data = [
    'name' => $name,
    'email' => $email,
    'subject' => $subject,
    'message' => $message,
    'notes' => $notes,
    'phone' => $phone,
    'ip' => '80.244.186.132',
    'topicId' => '10',
];

set_time_limit(30);

$options = [
    'http' => [
        'header' => "X-API-Key: {$API_KEY}",
        'method' => 'POST',
        'content' => json_encode($data),
    ],
];

$context = stream_context_create($options);
$result = file_get_contents($config['url'], false, $context);

if ($result === false) {
    die('FAILED');
}


print_r("<br><br>");
print_r($data);
print_r("<br><br>");
print_r($options);
print_r("<br><br>");

echo $result;