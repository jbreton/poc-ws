<?php

// Devristo Phpws client est event-driven

require_once(__DIR__."/../vendor/autoload.php");                // Composer autoloader

class JobState {
	public $status = 'processing';
}

$job_state = new JobState();

$loop = \React\EventLoop\Factory::create();

$logger = new \Zend\Log\Logger();
$writer = new Zend\Log\Writer\Stream("php://output");
$logger->addWriter($writer);

$client = new \Devristo\Phpws\Client\WebSocket("ws://10.100.1.2:12345/", $loop, $logger);

$client->on("message", function($message) use ($logger, $client, $job_state) {
	$data = $message->getData();
	$json = json_decode($data);
	if($json->eventType == 'test') {
		$logger->notice('received message : '.$json->data);
		
		if($job_state->status != 'completed') {
			if($json->data == 'cancel') {
				$job_state->status = 'cancelled';
			}
		}
	}
});

$client->on("connect", function() use ($logger, $client, $loop, $job_state){
    $logger->notice("connected!");
	
	$loop->addTimer(0.001, function() use ($logger, $client, $loop, $job_state) {
		do {
			if($job_state->status == 'cancelled') {
				$client->close();
				
				$logger->notice('cancelled');
				
				return;
			}
			
			$rand = rand(1, 1000);
			$json = array(
				'eventType' => 'test',
				'data' => $rand
			);
			
			$logger->notice($job_state->status);
			$logger->notice($rand);

			$client->send(json_encode($json));
			
			sleep(1);
			
			$loop->tick();
		} while($rand != 1);
		
		$job_state->status = 'completed';

		$client->close();
	});
});

$promise = $client->open();
$promise->then(function() use ($client, $logger) {
	$logger->notice('closed');
});

$loop->run();

$logger->notice('Done');

//class WebsocketClient
//{
//	private $_Socket = null;
// 
//	public function __construct($host, $port)
//	{
//		$this->_connect($host, $port);	
//	}
// 
//	public function __destruct()
//	{
//		$this->_disconnect();
//	}
// 
//	public function sendData($data)
//	{
//		// send actual data:
//		fwrite($this->_Socket, "\x00" . $data . "\xff" ) or die('Error:' . $errno . ':' . $errstr); 
//		$wsData = fread($this->_Socket, 2000);
//		$retData = trim($wsData,"\x00\xff");        
//		return $retData;
//	}
// 
//	private function _connect($host, $port)
//	{
//		$key1 = $this->_generateRandomString(32);
//		$key2 = $this->_generateRandomString(32);
//		$key3 = $this->_generateRandomString(8, false, true);		
// 
//		$header = "GET /echo HTTP/1.1\r\n";
//		$header.= "Upgrade: WebSocket\r\n";
//		$header.= "Connection: Upgrade\r\n";
//		$header.= "Host: ".$host.":".$port."\r\n";
//		$header.= "Origin: http://foobar.com\r\n";
//		$header.= "Sec-WebSocket-Key1: " . $key1 . "\r\n";
//		$header.= "Sec-WebSocket-Key2: " . $key2 . "\r\n";
//		$header.= "\r\n";
//		$header.= $key3;
// 
// 
//		$this->_Socket = fsockopen($host, $port, $errno, $errstr, 2); 
//		fwrite($this->_Socket, $header) or die('Error: ' . $errno . ':' . $errstr); 
//		$response = fread($this->_Socket, 2000);
// 
//		/**
//		 * @todo: check response here. Currently not implemented cause "2 key handshake" is already deprecated.
//		 * See: http://en.wikipedia.org/wiki/WebSocket#WebSocket_Protocol_Handshake
//		 */		
// 
//		return true;
//	}
// 
//	private function _disconnect()
//	{
//		fclose($this->_Socket);
//	}
// 
//	private function _generateRandomString($length = 10, $addSpaces = true, $addNumbers = true)
//	{  
//		$characters = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ!"§$%&/()=[]{}';
//		$useChars = array();
//		// select some random chars:    
//		for($i = 0; $i < $length; $i++)
//		{
//			$useChars[] = $characters[mt_rand(0, strlen($characters)-1)];
//		}
//		// add spaces and numbers:
//		if($addSpaces === true)
//		{
//			array_push($useChars, ' ', ' ', ' ', ' ', ' ', ' ');
//		}
//		if($addNumbers === true)
//		{
//			array_push($useChars, rand(0,9), rand(0,9), rand(0,9));
//		}
//		shuffle($useChars);
//		$randomString = trim(implode('', $useChars));
//		$randomString = substr($randomString, 0, $length);
//		return $randomString;
//	}
//}
// 
//$WebSocketClient = new WebsocketClient('10.100.1.2', 12345);
//
//$json = array(
//	'eventType' => 'test',
//	'data' => array(
//		'some' => 'data'
//	)
//);
//
//$WebSocketClient->sendData(json_encode($json));
//unset($WebSocketClient);