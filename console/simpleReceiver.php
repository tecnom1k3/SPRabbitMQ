<?php
chdir(dirname(__DIR__));

require_once('vendor/autoload.php');

use Acme\AmqpWrapper\SimpleReceiver;

$receiver = new SimpleReceiver();
$receiver->listen();