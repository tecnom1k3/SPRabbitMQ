<?php
chdir(dirname(__DIR__));

require_once('vendor/autoload.php');

use Acme\AmqpWrapper\WorkerReceiver;

$worker = new WorkerReceiver();

$worker->listen();