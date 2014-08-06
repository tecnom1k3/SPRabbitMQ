<?php
namespace Acme\AmqpWrapper;

use PhpAmqpLib\Connection\AMQPConnection;
use PhpAmqpLib\Message\AMQPMessage;

class RpcSender
{
	private $response;
	private $corr_id;
	
    public function execute($credentials) 
    {
        $connection = new AMQPConnection('localhost', 5672, 'guest', 'guest');
		$channel = $connection->channel();
		
		/*
		 * $callback_queue has a value like amq.gen-_U0kJVm8helFzQk9P0z9gg
		 */
		list($callback_queue, ,) = $channel->queue_declare("", false, false, true, false);
		
		$channel->basic_consume($callback_queue, '', false, false, false, false, array(
		    $this, 
		    'onResponse',
		    ));
			
		$this->response = null;
		
		/*
		 * $this->corr_id has a value like 53e26b393313a
		 */
		$this->corr_id = uniqid();
		
		$jsonCredentials = json_encode($credentials);

		$msg = new AMQPMessage($jsonCredentials, array(
		    'correlation_id' => $this->corr_id, 
		    'reply_to' => $callback_queue
		    ));
		    
		$channel->basic_publish($msg, '', 'rpc_queue');
		
		while(!$this->response) {
			$channel->wait();
		}
		
		$channel->close();
		$connection->close();
		
		return $this->response;
    }
    
    public function onResponse(AMQPMessage $rep) {
		if($rep->get('correlation_id') == $this->corr_id) {
			$this->response = $rep->body;
		}
	}
}