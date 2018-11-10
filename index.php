<?php
require_once __DIR__ . '/vendor/autoload.php';
require_once __DIR__ . '/Response.php';

$result = ['ok' => true, 'greeting' => 'Hi there!'];

// Send a response
$response= ResponseManager::getInstance(200, $result);
$response->send();

// Getting a sent response
echo "\n\n";
print_r($response->getResponse());

// Finishing the response
$response->finish();

// This code will not be executed
echo 'Are you have something else to do?';
