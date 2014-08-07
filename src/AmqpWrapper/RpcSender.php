<?php
namespace Acme\AmqpWrapper;

use PhpAmqpLib\Connection\AMQPConnection;
use PhpAmqpLib\Message\AMQPMessage;
use Monolog\Logger;
use Monolog\Handler\StreamHandler;

class RpcSender
{
    /**
     * @var string
     */
    private $response;

    /**
     * @var string
     */
    private $corr_id;

    /**
     * @var Logger
     */
    private $log;
    
    public function __construct()
    {
        $this->log = new Logger('rpcSend');
        $this->log->pushHandler(new StreamHandler('logs/rpcSend.log', Logger::INFO));
    }

    /**
     * @param array $credentials
     * @return string
     */
    public function execute($credentials)
    {
    	#In real life apps, never log credentials details
    	$this->log->addInfo('Recevied the credentials: ' . serialize($credentials));  
    	
        $connection = new AMQPConnection('localhost', 5672, 'guest', 'guest');
		$channel = $connection->channel();
		
		/*
		 * creates an anonymous exclusive callback queue
		 * $callback_queue has a value like amq.gen-_U0kJVm8helFzQk9P0z9gg
		 */
		list($callback_queue, ,) = $channel->queue_declare(
			"", 	#queue
			false, 	#passive
			false, 	#durable
			true, 	#exclusive
			false	#auto delete
			);
			
		$this->log->addInfo('Generated the callback queue: ' . $callback_queue);
		
		$channel->basic_consume(
			$callback_queue, 			#queue
			'', 						#consumer tag
			false, 						#no local
			false, 						#no ack
			false, 						#exclusive
			false, 						#no wait
			array($this, 'onResponse')	#callback
			);
			
		$this->response = null;
		
		/*
		 * $this->corr_id has a value like 53e26b393313a
		 */
		$this->corr_id = uniqid();
		$this->log->addInfo('Generated correlation id: ' . $this->corr_id);
		
		$jsonCredentials = json_encode($credentials);


		/*
		 * create a message with two properties: reply_to, which is set to the 
		 * callback queue and correlation_id, which is set to a unique value for 
		 * every request
		 */
		$msg = new AMQPMessage(
			$jsonCredentials, 															#body
			array('correlation_id' => $this->corr_id, 'reply_to' => $callback_queue)	#properties
			);
		    
		/*
		 * The request is sent to an rpc_queue queue.
		 */
		$channel->basic_publish(
			$msg,		#message 
			'', 		#exchange
			'rpc_queue'	#routing key
			);
		
		$this->log->addInfo('Published message into queue');
		
		while(!$this->response) {
			$this->log->addInfo('Waiting to receive response');
			$channel->wait();
		}
		
		$channel->close();
		$connection->close();
		
		return $this->response;
    }

    /**
     * When a message appears, it checks the correlation_id property. If it
     * matches the value from the request it returns the response to the
     * application.
     *
     * @param AMQPMessage $rep
     */
    public function onResponse(AMQPMessage $rep) {
    	$this->log->addInfo('Received response');
    	
		if($rep->get('correlation_id') == $this->corr_id) {
			$this->log->addInfo('Correlation id matches, setting response: ' . $rep->body);
			$this->response = $rep->body;
		}
	}
}