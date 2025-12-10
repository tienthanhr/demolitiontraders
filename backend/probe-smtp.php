<?php
// Public probe script to be called via HTTP for quick diagnostics
// Path: https://tienthanhr.site/backend/probe-smtp.php

header('Content-Type: application/json');

$host = 'smtp.office365.com';
$port = 587;
$timeout = 5;

$resolvedIp = gethostbyname($host);
$fp_ok = false; $fp_msg = '';
$fp = @fsockopen($host, $port, $errno, $errstr, $timeout);
if ($fp) { $fp_ok = true; $fp_msg = 'OK'; fclose($fp);} else { $fp_ok = false; $fp_msg = "$errno - $errstr"; }

$ctx = stream_context_create(['ssl' => ['verify_peer' => false, 'verify_peer_name' => false, 'allow_self_signed' => true]]);
$fp2_ok = false; $fp2_msg = '';
$fp2 = @stream_socket_client("tcp://$host:$port", $errno2, $errstr2, $timeout, STREAM_CLIENT_CONNECT, $ctx);
if ($fp2) { $fp2_ok = true; $fp2_msg = 'OK'; fclose($fp2); } else { $fp2_ok = false; $fp2_msg = "$errno2 - $errstr2"; }

echo json_encode([
    'success' => true,
    'host' => $host,
    'port' => $port,
    'resolved_ip' => $resolvedIp,
    'fsockopen' => ['ok' => $fp_ok, 'msg' => $fp_msg],
    'stream_socket_client' => ['ok' => $fp2_ok, 'msg' => $fp2_msg],
]);

?>