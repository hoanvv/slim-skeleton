<?php

/**
 * This file is for api /mail/send
 */
require_once  '../../vendor/autoload.php';

use PhpAmqpLib\Connection\AMQPStreamConnection;

$connection = new AMQPStreamConnection('rabbitmq', 5672, 'guest', 'guest');

$channel = $connection->channel();

/**
 * Message Durability
 * 
 * To avoid losing message even when RabbitMQ is down,
 * we need to mark both the queue and messages as durable
 * 
 * To do so we pass the THIRD parameter to queue_declare as TRUE
 * Also need to set the delivery_mode to persistent in AMQPMessage
 */
$queue = $_ENV['rabbitmq_queue'] ?? 'default_channel';
$channel->queue_declare($queue, false, true, false, false);

echo " [*] Waiting for messages. To exit press CTRL+C\n";
$dateTime = (new DateTimeImmutable())->format('Y-m-d H-i-s');
$callback = function ($data) use ($channel) {
    // Array input is encoded before sending
    $input = json_decode($data->body, true);
    $msg = $input['msg'];
    $delay = $input['delay'];
    $deliveryTag = $data->delivery_info['delivery_tag'];
    echo " [x] Received: ", $msg, " [x]\n";
    echo " [x] Processing tag $deliveryTag... [x]\n";
    sleep($delay);
    echo " [x] Finished after: $delay(s) [x]\n\n";
    $data->ack();
    // $channel->basic_reject($deliveryTag, false);
    // $data->nack();
};
/**
 * Don't dispatch a new message to a worker until it has processed and acknowledged the previous one
 * If all workers are busy, the queue still fill up
 */
$channel->basic_qos(null, 1, null);
/**
 * Message Acknowledgement
 * 
 * An ack(nowledgement) is sent back by the consumer to tell RabbitMQ 
 * that a particular message has been received, processed and that RabbitMQ is free to delete it
 * 
 * Turn them on by setting the FOURTH parameter to basic_consume to FALSE (TRUE means no ack)
 */

$channel->basic_consume($queue, '', false, false, false, false, $callback);

while ($channel->is_open()) {
    $channel->wait();
}

$channel->close();
$connection->close();
