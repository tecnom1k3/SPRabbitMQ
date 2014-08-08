<?php
namespace Acme\AmqpWrapper;

use PhpAmqpLib\Connection\AMQPConnection;
use PhpAmqpLib\Message\AMQPMessage;
use Monolog\Logger;
use Monolog\Handler\StreamHandler;

class WorkerSender
{
    /**
     * @var Logger
     */
    private $log;
    
    public function __construct()
    {
        $this->log = new Logger('workerSend');
        $this->log->pushHandler(new StreamHandler('logs/workerSend.log', Logger::INFO));
    }
    
    /**
     * Sends an invoice generation task to the workers
     * 
     * @param int $invoiceNum
     */ 
    public function execute($invoiceNum)
    {
        
        $this->log->addInfo('Received invoice for processing: ' . $invoiceNum);
        
        $connection = new AMQPConnection('localhost', 5672, 'guest', 'guest');
        
        $channel = $connection->channel();
        
        $channel->queue_declare(
            'invoice_queue',    #queue - Queue names may be up to 255 bytes of UTF-8 characters
            false,              #passive - can use this to check whether an exchange exists without modifying the server state
            true,               #durable, make sure that RabbitMQ will never lose our queue if a crash occurs - the queue will survive a broker restart
            false,              #exclusive - used by only one connection and the queue will be deleted when that connection closes
            false               #auto delete - queue is deleted when last consumer unsubscribes
            );
            
        $msg = new AMQPMessage(
            $invoiceNum,
            array('delivery_mode' => 2) # make message persistent, so it is not lost if server crashes or quits
            );
            
        $channel->basic_publish(
            $msg,               #message 
            '',                 #exchange
            'invoice_queue'     #routing key (queue)
            );
            
        $this->log->addInfo('Published task to worker');
            
        $channel->close();
        $connection->close();
    }
}