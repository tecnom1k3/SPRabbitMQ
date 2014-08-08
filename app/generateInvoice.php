<?php
chdir(dirname(__DIR__));
require_once('vendor/autoload.php');

use Acme\AmqpWrapper\WorkerSender;

$inputFilters = array(
    'invoiceNo' => FILTER_SANITIZE_NUMBER_INT,
);

$input = filter_input_array(INPUT_POST, $inputFilters);

$sender = new WorkerSender();

$sender->execute($input['invoiceNo']);