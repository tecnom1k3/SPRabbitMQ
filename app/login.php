<?php
chdir(dirname(__DIR__));

require_once('vendor/autoload.php');

use Acme\AmqpWrapper\RpcSender;

$inputFilters = array(
    'username' => FILTER_SANITIZE_STRING,
    'password' => FILTER_SANITIZE_STRING,
);

$input = filter_input_array(INPUT_POST, $inputFilters);

$rpc = new RpcSender();
$response = $rpc->execute($input);
echo $response;