#!/usr/bin/php -q
<?php
$config = array(
  'url'=> 'https://tickets.remoteit.co.uk/api/http.php/tickets.json',
);
$name = $_GET['name'];
$email = $_GET['email'];
$phone = $_GET['phone'];
$subject = $_GET['subject'];
$message = $_GET['message'];
$notes = $_GET['note'];
$data = array(
    'name'      =>    $name,
    'email'       =>    $email,
    'subject'    =>    $subject,
    'message' =>    $message,
    'notes'       =>    $notes,
    'phone'      =>    $phone,
	'topicId'   =>      '10',
);

set_time_limit(30);

// Load TICKET_API_KEY from the gitignored .env file at the site root
$apiKey = null;
$envPath = __DIR__.'/.env';
if (is_file($envPath)) {
    foreach (file($envPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
        if (strpos(trim($line), '#') === 0 || strpos($line, '=') === false)
            continue;
        list($envKey, $envValue) = array_map('trim', explode('=', $line, 2));
        if ($envKey === 'TICKET_API_KEY')
            $apiKey = $envValue;
    }
}
if (!$apiKey)
    die("Ticket Creation Failed - Missing TICKET_API_KEY in .env. <br/> Please call Tetrabyte.");

$options = array(
  'http' => array(
    'header'  => "X-API-Key: $apiKey",
    'method' => 'POST',
    'content' => json_encode($data)
   )
);
$context  = stream_context_create($options);
$result = file_get_contents($config['url'], false, $context);
if ($result === FALSE) { die("FAILED"); }

echo($result);

?>
