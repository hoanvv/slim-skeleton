<?php

/**
 * This file is for api mail/publish
 */
require_once  '../../vendor/autoload.php';

use PhpAmqpLib\Connection\AMQPStreamConnection;

$connection = new AMQPStreamConnection('rabbitmq', 5672, 'guest', 'guest');

$channel = $connection->channel();

// Connect exchange channel
$channel->exchange_declare('rabbit_logs', 'fanout', false, false, false);

// Create temporarily queue
list($queueName,,) = $channel->queue_declare("", false, false, true, false);
$channel->queue_bind($queueName, 'rabbit_logs');

echo " [*] This is queue: $queueName\n";
echo " [*] Waiting for logs. To exit press CTRL+C\n";

$callback = function ($data) {
    $input = json_decode($data->body, true);
    $msg = $input['msg'];
    echo ' [x] ', $msg, "\n";
};

$channel->basic_consume($queueName, '', false, true, false, false, $callback);

while ($channel->is_open()) {
    $channel->wait();
}

$channel->close();
$connection->close();
