<?php
header('Content-Type: text/plain');
echo "DB_HOST from getenv: " . getenv('DB_HOST') . "\n";
echo "DB_HOST from \$_ENV: " . ($_ENV['DB_HOST'] ?? 'not set') . "\n";
echo "DB_HOST from \$_SERVER: " . ($_SERVER['DB_HOST'] ?? 'not set') . "\n";

echo "\nAll Env Vars:\n";
print_r(getenv());
?>
