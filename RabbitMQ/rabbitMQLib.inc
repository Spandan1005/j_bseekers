<?php

require_once('get_host_info.inc');

//adding in a TLS block to encorporate tls stuff - SP 11/12
function applyTls(array $params) {
	$creds = require __DIR__ . '/rabbitCreds.php'; //using same convetion to refer to rabbit creds to get MQ deets
	if (!empty($creds['TLS_CA'])) { //applkying the TLS cert here
		$params['port'] = 5671; //chanigng to port 5671
		$params['cacert'] = $creds['TLS_CA']; //talkin about the CA as mentioned earlier thats issued through rabbit to all machines 
		$params['verify'] = true;
	}
	return $params;
}

class rabbitMQServer
{
	private $machine = "";
	public  $BROKER_HOST;
	private $BROKER_PORT;
	private $USER;
	private $PASSWORD;
	private $VHOST;
	private $exchange;
	private $queue;
	private $routing_key = '*';
	private $exchange_type = "topic";
	private $auto_delete = false;

	function __construct($machine, $server = "rabbitMQ")
	{
		$this->machine = getHostInfo(array($machine));
		//$this->BROKER_HOST   = $this->machine[$server]["BROKER_HOST"]; //SP 11/10
		//$this->BROKER_PORT   = $this->machine[$server]["BROKER_PORT"];

		//$this->USER     = $this->machine[$server]["USER"];
		//$this->PASSWORD = $this->machine[$server]["PASSWORD"];
		//$this->VHOST = $this->machine[$server]["VHOST"];
		//
		// retired old credential calls.  now referring to gitignored php file for creds.
		// Andrew Galella - 10.1.2026					

		$creds = require __DIR__ . '/rabbitCreds.php';
		$this->USER = $creds['USER'];
		$this->PASSWORD = $creds['PASSWORD'];
		$this->VHOST = $creds['VHOST'];
		$this->BROKER_HOST = $creds['BROKER_HOST']; //added this in prior to db merge and changed it to localhost SP 11/10
		$this->BROKER_PORT = $creds['BROKER_PORT']; // AG 11/12 trying to add SSL/TLS

		if (isset( $this->machine[$server]["EXCHANGE_TYPE"]))
		{
			$this->exchange_type = $this->machine[$server]["EXCHANGE_TYPE"];
		}
		if (isset( $this->machine[$server]["AUTO_DELETE"]))
		{
			$this->auto_delete = $this->machine[$server]["AUTO_DELETE"];
		}
		if (isset( $this->machine[$server]["ROUTING_KEY"]))
		{
			$this->routing_key = $this->machine[$server]["ROUTING_KEY"];
		}
		$this->exchange = $this->machine[$server]["EXCHANGE"];
		$this->queue = $this->machine[$server]["QUEUE"];
	}

	function process_message($msg)
	{
		// send the ack to clear the item from the queue
		/*
		if ($msg->getRoutingKey() !== "*")
    {
      return;
	}
		*/
    $this->conn_queue->ack($msg->getDeliveryTag());
		try
		{
			if ($msg->getReplyTo())
			{
				// message wants a response
				// process request
				$body = $msg->getBody();
				$payload = json_decode($body, true);
				$response;
				if (isset($this->callback))
				{
					$response = call_user_func($this->callback,$payload);
				}

      $params = array();
      $params['host'] = $this->BROKER_HOST;
      $params['port'] = $this->BROKER_PORT;
      $params['login'] = $this->USER;
      $params['password'] = $this->PASSWORD;
      $params['vhost'] = $this->VHOST;

      			//adding in for tls block SP 11/12
      			$params = applyTls($params);
      			$conn = new AMQPConnection($params);
			$conn->connect();
			$channel = new AMQPChannel($conn);
			$exchange = new AMQPExchange($channel);
      $exchange->setName($this->exchange);
      $exchange->setType($this->exchange_type);

			$conn_queue = new AMQPQueue($channel);
			$conn_queue->setName($msg->getReplyTo());
			$replykey = $this->routing_key.".response";
			$conn_queue->bind($exchange->getName(),$replykey);
			$exchange->publish(json_encode($response),$replykey,AMQP_NOPARAM,array('correlation_id'=>$msg->getCorrelationId()));

				return;
			}
		}
		catch(Exception $e)
		{
			// ampq throws exception if get fails...
            echo "error: rabbitMQServer: process_message: exception caught: ".$e;
		}
		// message does not require a response, send ack immediately
		$body = $msg->getBody();
		$payload = json_decode($body, true);
		if (isset($this->callback))
		{
			call_user_func($this->callback,$payload);
		}
		echo "processed one-way message\n";
	}

	function process_requests($callback)
	{
		try
		{
			$this->callback = $callback;
      $params = array();
      $params['host'] = $this->BROKER_HOST;
      $params['port'] = $this->BROKER_PORT;
      $params['login'] = $this->USER;
      $params['password'] = $this->PASSWORD;
      $params['vhost'] = $this->VHOST;
      			
      			//adding in this for tls block
      			$params = applyTls($params);
      			$conn = new AMQPConnection($params);
			$conn->connect();

			$channel = new AMQPChannel($conn);

			$exchange = new AMQPExchange($channel);
      $exchange->setName($this->exchange);
      $exchange->setType($this->exchange_type);

			$this->conn_queue = new AMQPQueue($channel);
			$this->conn_queue->setName($this->queue);
			$this->conn_queue->bind($exchange->getName(),$this->routing_key);

			$this->conn_queue->consume(array($this,'process_message'));

			// Loop as long as the channel has callbacks registered
			while (count($channel->callbacks))
			{
				$channel->wait();
			}
		}
		catch (Exception $e)
		{
			trigger_error("Failed to start request processor: ".$e,E_USER_ERROR); 
		}
	}
}

class rabbitMQClient
{
	private $machine = "";
	public  $BROKER_HOST;
	private $BROKER_PORT;
	private $USER;
	private $PASSWORD;
	private $VHOST;
	private $exchange;
	private $queue;
	private $routing_key = '*';
	private $response_queue = array();
	private $exchange_type = "topic";

	function __construct($machine, $server = "rabbitMQ")
	{
		$this->machine = getHostInfo(array($machine));
		//$this->BROKER_HOST   = $this->machine[$server]["BROKER_HOST"]; //commented this out SP 11/10
		//$this->BROKER_PORT   = $this->machine[$server]["BROKER_PORT"];

		//$this->USER     = $this->machine[$server]["USER"];
		//$this->PASSWORD = $this->machine[$server]["PASSWORD"];
		//$this->VHOST = $this->machine[$server]["VHOST"];
		//
		// retired old credential calls.  now referring to gitignored php file for creds.
		// Andrew Galella - 10.1.2026

		$creds = require __DIR__ . '/rabbitCreds.php';
		$this->USER = $creds['USER'];
		$this->PASSWORD = $creds['PASSWORD'];
		$this->VHOST = $creds['VHOST'];
		$this->BROKER_HOST = $creds['BROKER_HOST']; //added in this line prior to db merge so it references localhost SP 11/10
		$this->BROKER_PORT = $creds['BROKER_PORT']; // AG 11/12 trying to add SSL/TLS

		if (isset( $this->machine[$server]["EXCHANGE_TYPE"]))
		{
			$this->exchange_type = $this->machine[$server]["EXCHANGE_TYPE"];
		}
		if (isset( $this->machine[$server]["AUTO_DELETE"]))
		{
			$this->auto_delete = $this->machine[$server]["AUTO_DELETE"];
		}
		if (isset( $this->machine[$server]["ROUTING_KEY"]))
		{
			$this->routing_key = $this->machine[$server]["ROUTING_KEY"];
		} 	
		$this->exchange = $this->machine[$server]["EXCHANGE"];
		$this->queue = $this->machine[$server]["QUEUE"];
	}

	function process_response($response)
	{
		$uid = $response->getCorrelationId();
		if (!isset($this->response_queue[$uid]))
		{
		  echo  "unknown uid\n";
		  return true;
		}
    $this->conn_queue->ack($response->getDeliveryTag());
		$body = $response->getBody();
		$payload = json_decode($body, true);
		if (!(isset($payload)))
		{
			$payload = "[empty response]";
		}
		$this->response_queue[$uid] = $payload;
		return false;
	}

	function send_request($message)
	{
		$uid = uniqid();

		$json_message = json_encode($message);
		try
		{
      $params = array();
      $params['host'] = $this->BROKER_HOST;
      $params['port'] = $this->BROKER_PORT;
      $params['login'] = $this->USER;
      $params['password'] = $this->PASSWORD;
      $params['vhost'] = $this->VHOST;

      			//adding this line in as well for the tls block above SP 11/12
      			$params = applyTls($params);
      			$conn = new AMQPConnection($params);
			$conn->connect();

			$channel = new AMQPChannel($conn);

			$exchange = new AMQPExchange($channel);
      $exchange->setName($this->exchange);
      $exchange->setType($this->exchange_type);

      $callback_queue = new AMQPQueue($channel);
      $callback_queue->setFlags(AMQP_AUTODELETE);  // same names led to a lot of hanging
	  //$callback_queue->setName($this->queue."_response");
      $callback_queue->declare();
			$callback_queue->bind($exchange->getName(),$this->routing_key.".response");

			$this->conn_queue = new AMQPQueue($channel);
			$this->conn_queue->setName($this->queue);
			$this->conn_queue->bind($exchange->getName(),$this->routing_key);

			$exchange->publish($json_message,$this->routing_key,AMQP_NOPARAM,array('reply_to'=>$callback_queue->getName(),'correlation_id'=>$uid));
      $this->response_queue[$uid] = "waiting";
			$callback_queue->consume(array($this,'process_response'));

			$response = $this->response_queue[$uid];
			unset($this->response_queue[$uid]);
			return $response;
		}
		catch(Exception $e)
		{
			die("failed to send message to exchange: ". $e->getMessage()."\n");
		}
	}

	/**
	  @brief send a one-way message to the server.  These are
	  auto-acknowledged and give no response.

	  @param message the body of the request.  This must make sense to the
	  server
	 */
	function publish($message)
	{
		$json_message = json_encode($message);
		try
		{
      $params = array();
      $params['host'] = $this->BROKER_HOST;
      $params['port'] = $this->BROKER_PORT;
      $params['login'] = $this->USER;
      $params['password'] = $this->PASSWORD;
      $params['vhost'] = $this->VHOST;
      			
      			//adding this line in to correspond with the new Tls addition above SP 11/12
      			$params = applyTls($params);
      			$conn = new AMQPConnection($params);
			$conn->connect();
			$channel = new AMQPChannel($conn);
			$exchange = new AMQPExchange($channel);
      $exchange->setName($this->exchange);
      $exchange->setType($this->exchange_type);
			$this->conn_queue = new AMQPQueue($channel);
			$this->conn_queue->setName($this->queue);
			$this->conn_queue->bind($exchange->getName(),$this->routing_key);
			return $exchange->publish($json_message,$this->routing_key);
		}
		catch(Exception $e)
		{
			die("failed to send message to exchange: ". $e->getMessage()."\n");
		}
	}
}
?>

