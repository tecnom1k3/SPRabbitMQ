<?php
namespace Acme\AmqpWrapper;

use PhpAmqpLib\Connection\AMQPConnection;
use PhpAmqpLib\Message\AMQPMessage;

class RpcReceiver
{
    public function listen() 
    {
        $connection = new AMQPConnection('localhost', 5672, 'guest', 'guest');
        $channel = $connection->channel();
        
        $channel->queue_declare('rpc_queue', false, false, false, false);
        
        echo " [x] Awaiting RPC requests\n";
        
        $channel->basic_qos(null, 1, null);
        $channel->basic_consume('rpc_queue', '', false, false, false, false, array(
            $this,
            'callback'
            ));
        
        while(count($channel->callbacks)) {
            $channel->wait();
        }
        
        $channel->close();
        $connection->close();
    }
    
    public function callback(AMQPMessage $req) {
        $credentials = json_decode($req->body);
    	print_r($credentials);
    	
    	$authResult = $this->auth($credentials);
    
    	$msg = new AMQPMessage(json_encode(array(
    	    'status' => $authResult
    	    )), array(
    	    'correlation_id' => $req->get('correlation_id')
    	    ));
    
    	$req->delivery_info['channel']->basic_publish($msg, '', $req->get('reply_to'));
    	$req->delivery_info['channel']->basic_ack($req->delivery_info['delivery_tag']);
    }
    
    private function auth(\stdClass $credentials) {
    	if (($credentials->username == 'admin') && ($credentials->password == 'admin')) {
    	    return true;
    	} else {
    	    return false;
    	}
    }
}