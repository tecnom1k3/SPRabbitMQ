<?php
namespace Acme\AmqpWrapper;

use PhpAmqpLib\Connection\AMQPConnection;
use Monolog\Logger;
use Monolog\Handler\StreamHandler;

class SimpleReceiver
{
    private $log;
    
    public function __construct()
    {
        $this->log = new Logger('pizzas');
        $this->log->pushHandler(new StreamHandler('logs/pizza.log', Logger::INFO));
    }
    
    public function listen() 
    {
        $connection = new AMQPConnection('localhost', 5672, 'guest', 'guest');
        $channel = $connection->channel();
        
        
        $channel->queue_declare('pizzaTime', false, false, false, false);
        
        $callback = $this->getClosure();
        
        $channel->basic_consume('pizzaTime', '', false, true, false, false, $callback);
        
        while(count($channel->callbacks)) {
            $channel->wait();
        }
        
        $channel->close();
        $connection->close();
    }
    
    public function getClosure()
    {
        $log = $this->log;
        
        return function($msg) use ($log) {
            echo " [x] Received ", $msg->body, "\n";
            
            $log->addInfo($msg->body);
        };
    }
}