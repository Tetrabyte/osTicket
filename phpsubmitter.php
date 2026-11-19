<?php
// Rate limit (security review SR-12, Ashley 2026-11-19): at most 60 requests per rolling 60 minutes per client IP, then HTTP 429.
// Done here, at the origin, because tickets.remoteit.co.uk is not proxied through Cloudflare (some downloads, such as the .ps1 files, break behind it).
// State: one small file per IP in <system temp>/phpsubmitter-rl/ (survives nothing important: a cleared temp dir just resets the counters).
// It FAILS OPEN: if the counter cannot be kept for any reason the request is allowed, so a fault here can never lock out a real user.
function phpsubmitter_rate_limited($ip, $limit = 60, $window = 3600, $now = null) {
    $now = $now === null ? time() : $now;
    $dir = sys_get_temp_dir() . '/phpsubmitter-rl';
    if (!is_dir($dir) && !@mkdir($dir, 0700, true) && !is_dir($dir)) {
        return false;
    }
    $fh = @fopen($dir . '/' . sha1($ip), 'c+');
    if (!$fh) {
        return false;
    }
    flock($fh, LOCK_EX);
    $seen = array_filter(array_map('intval', explode(',', (string) stream_get_contents($fh))), function ($t) use ($now, $window) {
        return $t > $now - $window;
    });
    $blocked = count($seen) >= $limit;
    if (!$blocked) {
        $seen[] = $now;                       // a refused request is not counted, so the window frees up as old requests age out
    }
    ftruncate($fh, 0);
    rewind($fh);
    fwrite($fh, implode(',', $seen));
    fflush($fh);
    flock($fh, LOCK_UN);
    fclose($fh);
    if (mt_rand(1, 200) === 1) {              // now and then tidy counters nobody has touched for two windows
        foreach (glob($dir . '/*') ?: array() as $old) {
            if (@filemtime($old) < $now - 2 * $window) {
                @unlink($old);
            }
        }
    }
    return $blocked;
}
if (phpsubmitter_rate_limited(isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : 'unknown')) {
    http_response_code(429);
    header('Retry-After: 3600');
    die('Too many requests. Please try again later.');
}
// (this block sits ABOVE the old "#!/usr/bin/php -q" first line on purpose: nothing has been output yet, so the 429 status can always be sent)
?>
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
