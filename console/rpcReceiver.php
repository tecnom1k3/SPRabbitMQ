<?php
chdir(dirname(__DIR__));

require_once('vendor/autoload.php');

use Acme\AmqpWrapper\RpcReceiver;

$rpc = new RpcReceiver();
$rpc->listen();