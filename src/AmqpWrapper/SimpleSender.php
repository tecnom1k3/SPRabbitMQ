<?php
namespace Acme\AmqpWrapper;

use PhpAmqpLib\Connection\AMQPConnection;
use PhpAmqpLib\Message\AMQPMessage;


class SimpleSender
{
    /**
     * Sends a message to the pizzaTime queue.
     * 
     * @param string $message
     */
    public function execute($message)
    {
        $connection = new AMQPConnection('localhost', 5672, 'guest', 'guest');
        
        /* @var PhpAmqpLib\Channel\AMQPChannel $channel */
        $channel = $connection->channel();
        
        $channel->queue_declare('pizzaTime', false, false, false, false);
        
        $msg = new AMQPMessage($message);
        $channel->basic_publish($msg, '', 'pizzaTime');
        
        $channel->close();
        $connection->close();
    }
}