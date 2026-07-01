<?php

declare(strict_types=1);

/**
 * Interner Mail-Worker (nur localhost).
 * Läuft unter Apache/mod_php — SMTP funktioniert dort zuverlässig, im CLI-Worker nicht.
 */
$remoteIp = (string) ($_SERVER['REMOTE_ADDR'] ?? '');
if (!in_array($remoteIp, ['127.0.0.1', '::1'], true)) {
    http_response_code(403);
    header('Content-Type: text/plain; charset=UTF-8');
    echo 'Forbidden';
    exit;
}

$jobPath = trim((string) ($_GET['job'] ?? ''));
if ($jobPath === '' || !is_file($jobPath)) {
    http_response_code(404);
    header('Content-Type: text/plain; charset=UTF-8');
    echo 'Job not found';
    exit;
}

$realJob = realpath($jobPath);
$tempDir = realpath(sys_get_temp_dir());
if (
    $realJob === false
    || $tempDir === false
    || !str_starts_with($realJob, $tempDir . DIRECTORY_SEPARATOR)
    || !preg_match('#^easytime-mail-\d+-[a-f0-9]+\.json$#', basename($realJob))
) {
    http_response_code(400);
    header('Content-Type: text/plain; charset=UTF-8');
    echo 'Invalid job';
    exit;
}

set_time_limit(60);
ignore_user_abort(true);

$body = "OK\n";
header('Content-Type: text/plain; charset=UTF-8');
header('Content-Length: ' . (string) strlen($body));
header('Connection: close');
echo $body;

if (function_exists('fastcgi_finish_request')) {
    fastcgi_finish_request();
} else {
    @ini_set('zlib.output_compression', '0');
    @ini_set('implicit_flush', '1');
    if (function_exists('apache_setenv')) {
        @apache_setenv('no-gzip', '1');
    }
    while (ob_get_level() > 0) {
        ob_end_flush();
    }
    flush();
}

$argc = 2;
$argv = [__FILE__, $realJob];
require dirname(__DIR__) . '/scripts/dispatch-notification-mail.php';
