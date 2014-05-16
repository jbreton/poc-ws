<?php

require_once(__DIR__."/../vendor/autoload.php");
use Devristo\Phpws\Server\WebSocketServer;

$loop = \React\EventLoop\Factory::create();

// Create a logger which writes everything to the STDOUT
$logger = new \Zend\Log\Logger();
$writer = new Zend\Log\Writer\Stream("php://output");
$logger->addWriter($writer);

// Create a WebSocket server using SSL
$server = new WebSocketServer("tcp://10.100.1.2:12345", $loop, $logger);

$server->on('connect', function($client) use ($server, $logger) {
	$logger->notice($client->getIp().'~'.$client->getId().' has connected');
});

$server->on('message', function($client, $message) use ($server, $logger) {
	$logger->notice($client->getIp().'~'.$client->getId().' has send '.print_r($message->getData(), 1));
	
	$data = $message->getData();
	$json = json_decode($data);
	$logger->notice(print_r($json, 1));
	
	foreach($server->getConnections() as $ws_client) {
		if($ws_client->getId() != $client->getId()) {
			$logger->notice('Sending message to '.$ws_client->getId());
			$ws_client->sendString($data);
		}
	}
});

// Bind the server
$server->bind();

// Start the event loop
$loop->run();