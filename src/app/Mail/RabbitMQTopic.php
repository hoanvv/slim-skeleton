<?php

/**
 * This file is for api mail/topic
 */
require_once  '../../vendor/autoload.php';

use PhpAmqpLib\Connection\AMQPStreamConnection;

$connection = new AMQPStreamConnection('rabbitmq', 5672, 'guest', 'guest');

$channel = $connection->channel();

// Connect exchange channel
$channel->exchange_declare('rabbit_topic', 'topic', false, false, false);

// Create temporarily queue
list($queueName,,) = $channel->queue_declare("", false, false, true, false);
$bindingKeys = array_slice($argv, 1);
if (empty($bindingKeys)) {
    file_put_contents('php://stderr', "Usage: \$argv[0] [binding_key]\n");
    exit(1);
}
foreach ($bindingKeys as $bindingKey) {
    $channel->queue_bind($queueName, 'rabbit_topic', $bindingKey);
}

echo " [*] This is queue: $queueName for binding key: $bindingKey\n";
echo " [*] Waiting for logs. To exit press CTRL+C\n";

$callback = function ($data) {
    $input = json_decode($data->body, true);
    $msg = $input['msg'];

    echo ' [x] ', $data->delivery_info['routing_key'] . ' ' . $msg, "\n";
};

$channel->basic_consume($queueName, '', false, true, false, false, $callback);

while ($channel->is_open()) {
    $channel->wait();
}

$channel->close();
$connection->close();
