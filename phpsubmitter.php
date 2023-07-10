#!/usr/bin/php -q
<?php
include_once('include/api-config.php');
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
    'ip'             =>    '80.244.186.132',
	'topicId'   =>      '10',
);

set_time_limit(30);
$options = array(
  'http' => array(
    'header'  => "X-API-Key: ".$API_KEY,
    'method' => 'POST',
    'content' => json_encode($data)
   )
);
$context  = stream_context_create($options);
$result = file_get_contents($config['url'], false, $context);
if ($result === FALSE) { die("FAILED"); }

print_r("<br><br>");
print_r($data);
print_r("<br><br>");
print_r($context);
echo($result);

?>