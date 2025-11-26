<?php
$pdo = new PDO("mysql:host=localhost;dbname=mydb;charset=utf8", "user", "pass");
require 'vendor/autoload.php';
$xeroClientId     = "YOUR_XERO_CLIENT_ID";
$xeroClientSecret = "YOUR_XERO_CLIENT_SECRET";
$xeroRedirect     = "https://yourwebsite.com/xero-callback.php";
?>