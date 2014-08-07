<?php
namespace Acme\AmqpWrapper;

use PhpAmqpLib\Connection\AMQPConnection;
use PhpAmqpLib\Message\AMQPMessage;
use Monolog\Logger;
use Monolog\Handler\StreamHandler;

class SimpleSender
{
    /**
     * @var Logger
     */
    private $log;
    
    public function __construct()
    {
        $this->log = new Logger('simpleSend');
        $this->log->pushHandler(new StreamHandler('logs/simpleSend.log', Logger::INFO));
    }
    
    /**
     * Sends a message to the pizzaTime queue.
     * 
     * @param string $message
     */
    public function execute($message)
    {
        
        $this->log->addInfo('Received message to send: ' . $message);
        
        $connection = new AMQPConnection(
            'localhost',    #host 
            5672,           #port
            'guest',        #user
            'guest'         #password
            );


        /** @var $channel AMQPChannel */
        $channel = $connection->channel();
        
        $channel->queue_declare(
            'pizzaTime',    #queue name
            false,          #passive
            false,          #durable
            false,          #exclusive
            false           #autodelete
            );
        
        $msg = new AMQPMessage($message);
        
        $channel->basic_publish(
            $msg,           #message 
            '',             #exchange
            'pizzaTime'     #routing key
            );
            
        $this->log->addInfo('Message sent');
        
        $channel->close();
        $connection->close();
    }
}