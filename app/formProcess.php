<?php
chdir(dirname(__DIR__));

require_once('vendor/autoload.php');

use Acme\AmqpWrapper\SimpleSender;

$theName = filter_input(INPUT_POST, 'theName', FILTER_SANITIZE_STRING);

$simpleSender = new SimpleSender();

$simpleSender->execute($theName);

header("Location: orderReceived.html");
