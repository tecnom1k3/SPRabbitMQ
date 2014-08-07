<?php
namespace Acme\AmqpWrapper;

use PhpAmqpLib\Connection\AMQPConnection;
use PhpAmqpLib\Message\AMQPMessage;
use Monolog\Logger;
use Monolog\Handler\StreamHandler;

class RpcReceiver
{
    /**
     * @var Logger
     */
    private $log;
    
    public function __construct()
    {
        $this->log = new Logger('rpcReceive');
        $this->log->pushHandler(new StreamHandler('logs/rpcReceive.log', Logger::INFO));
    }

    /**
     * Listens for incoming messages
     */
    public function listen()
    {
        $this->log->addInfo('Begin listen routine');
        
        $connection = new AMQPConnection('localhost', 5672, 'guest', 'guest');
        $channel = $connection->channel();
        
        $channel->queue_declare(
            'rpc_queue',    #queue 
            false,          #passive
            false,          #durable
            false,          #exclusive
            false           #autodelete
            );
        
        $channel->basic_qos(
            null,   #prefetch size
            1,      #prefetch count
            null    #global
            );
            
        $channel->basic_consume(
            'rpc_queue',                #queue
            '',                         #consumer tag
            false,                      #no local
            false,                      #no ack
            false,                      #exclusive
            false,                      #no wait
            array($this, 'callback')    #callback
            );
            
        $this->log->addInfo('Consuming from queue');
        
        while(count($channel->callbacks)) {
            $this->log->addInfo('Waiting for incoming messages');
            $channel->wait();
        }
        
        $channel->close();
        $connection->close();
    }

    /**
     * Executes when a message is received.
     *
     * @param AMQPMessage $req
     */
    public function callback(AMQPMessage $req) {
        
        $this->log->addInfo('Received message: ' . $req->body);
        
        $credentials = json_decode($req->body);
    	
    	$authResult = $this->auth($credentials);

    	/*
    	 * Creating a reply message with the same correlation id than the incoming message
    	 */
    	$msg = new AMQPMessage(
    	    json_encode(array('status' => $authResult)),            #message
    	    array('correlation_id' => $req->get('correlation_id'))  #options
    	    );
    
    	$this->log->addInfo('Created response: ' . $msg->body . ' for correlation id: ' . $req->get('correlation_id'));
    	
    	/*
    	 * Publishing to the same channel from the incoming message
    	 */
    	$req->delivery_info['channel']->basic_publish(
    	    $msg,                   #message
    	    '',                     #exchange
    	    $req->get('reply_to')   #routing key
    	    );
    	    
    	$this->log->addInfo('Published response, replying to: ' . $req->get('reply_to'));
    	
    	/*
    	 * Acknowledging the message
    	 */
    	$req->delivery_info['channel']->basic_ack(
    	    $req->delivery_info['delivery_tag'] #delivery tag
    	    );
    	    
    	$this->log->addInfo('Acknowledged message to delivery tag: ' . $req->delivery_info['delivery_tag']);
    }

    /**
     * @param \stdClass $credentials
     * @return bool
     */
    private function auth(\stdClass $credentials) {
    	if (($credentials->username == 'admin') && ($credentials->password == 'admin')) {
    	    return true;
    	} else {
    	    return false;
    	}
    }
}