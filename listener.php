<?php

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/vendor/autoload.php';
use PhpAmqpLib\Connection\AMQPStreamConnection;

$connection = new AMQPStreamConnection($rabbitMQHost, $rabbitMQport, $rabbitMQUser, $rabbitMQPassword);
$channel = $connection->channel();

$channel->queue_declare($rabbitMQQueueName, false, false, false, false);
$channel->queue_bind($rabbitMQQueueName, $rabbitMQExchangeName);

echo ' [*] Waiting for chat msgs. To exit press CTRL+C', "\n";

$callback = function($msg){
    echo ' [x] ', $msg->body, "\n";

    $myfile = 'chat_log.txt';
    $handle = fopen($myfile, 'a') or die('Cannot open file:  '.$myfile);
    fwrite($handle, $msg->body."\n");
    fclose($handle);
};

$channel->basic_consume($rabbitMQQueueName, '', false, true, false, false, $callback);

while(count($channel->callbacks)) {
    $channel->wait();
}

$channel->close();
$connection->close();

