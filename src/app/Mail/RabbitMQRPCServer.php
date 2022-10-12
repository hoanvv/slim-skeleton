<?php

require_once  '../../vendor/autoload.php';

use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;

/**
 * RPC server side
 * 
 * Receive request from queue and return response to corresponding request
 */
$connection = new AMQPStreamConnection('rabbitmq', 5672, 'guest', 'guest');

$channel = $connection->channel();

// Connect exchange channel
$channel->queue_declare('rabbit_rpc', false, false, false, false);
// Create dump func for RPC
function fib($n)
{
    if ($n == 0) {
        return 0;
    }
    if ($n == 1) {
        return 1;
    }
    return fib($n - 1) + fib($n - 2);
}
echo " [x] Awaiting RPC requests\n";
$callback = function ($req) {
    try {
        $n = intval($req->body);
        echo ' [.] fib(', $n, ")\n";

        $msg = new AMQPMessage(
            (string) "fib($n) is: " . fib($n),
            array('correlation_id' => $req->get('correlation_id'))
        );

        $req->delivery_info['channel']->basic_publish(
            $msg,
            '',
            $req->get('reply_to')
        );
        $req->ack();
    } catch (\Exception $e) {
        $req->nack();
    }
};

$channel->basic_qos(null, 1, null);
$channel->basic_consume('rabbit_rpc', '', false, false, false, false, $callback);

while ($channel->is_open()) {
    $channel->wait();
}

$channel->close();
$connection->close();
