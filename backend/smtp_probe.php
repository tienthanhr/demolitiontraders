<?php
// Simple SMTP probe script to be run in the deployed environment (Railway) to test TCP connectivity
$host = 'smtp.office365.com';
$port = 587;
$timeout = 5;

echo "Testing socket connectivity to $host:$port (timeout {$timeout}s)\n";
$fp = @fsockopen($host, $port, $errno, $errstr, $timeout);
if ($fp) {
    echo "OK: Connected to $host:$port\n";
    fclose($fp);
} else {
    echo "FAIL: $errno - $errstr\n";
}

// Optional: attempt a TLS-only socket (check connect/connect+tls negotiation)
$context = stream_context_create(['ssl'=>['capture_peer_cert'=>true,'verify_peer'=>false,'verify_peer_name'=>false]]);
$fp2 = @stream_socket_client("tcp://$host:$port", $errno2, $errstr2, $timeout, STREAM_CLIENT_CONNECT, $context);
if ($fp2) {
    echo "OK: stream_socket_client connected to $host:$port\n";
    fclose($fp2);
} else {
    echo "FAIL: stream_socket_client $errno2 - $errstr2\n";
}

// End
