<?php

require_once  '../../vendor/autoload.php';

use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;

/**
 * RPC Client side
 * 
 * Send a request to queue and waiting for a response
 */
class FibonacciRpcClient
{
    private $connection;
    private $channel;
    private $callback_queue;
    private $response;
    private $corr_id;

    public function __construct()
    {
        $this->connection = new AMQPStreamConnection(
            'rabbitmq',
            5672,
            'guest',
            'guest'
        );
        $this->channel = $this->connection->channel();
        $this->channel->confirm_select();
        $this->channel->set_ack_handler(
            function (AMQPMessage $message) {
                // code when message is confirmed
                echo "Message ACKED with content " . $message->body . PHP_EOL;
            }
        );
        $this->channel->set_nack_handler(
            function (AMQPMessage $message) {
                // code when message is nack-ed
                echo "Message NACKED with content " . $message->body . PHP_EOL;
            }
        );
        list($this->callback_queue,,) = $this->channel->queue_declare(
            "",
            false,
            false,
            true,
            false
        );
        $this->channel->basic_consume(
            $this->callback_queue,
            '',
            false,
            false,
            false,
            false,
            array(
                $this,
                'onResponse'
            )
        );
    }

    public function onResponse($rep)
    {
        if ($rep->get('correlation_id') == $this->corr_id) {
            $this->response = $rep->body;
        }
    }

    public function call($n)
    {
        $this->response = null;
        $this->corr_id = uniqid();

        $msg = new AMQPMessage(
            (string) $n,
            array(
                'correlation_id' => $this->corr_id,
                'reply_to' => $this->callback_queue
            )
        );
        $this->channel->basic_publish($msg, '', 'rabbit_rpc');
        // uses a 5 second timeout
        $this->channel->wait_for_pending_acks(3.000);
        while (!$this->response) {
            $this->channel->wait();
        }
        return $this->response;
    }
}

$fibonacci_rpc = new FibonacciRpcClient();
$num = array_slice($argv, 1);
$num = empty($num) ? 30 : $num[0];
echo " Calculating fib($num)...\n";
$response = $fibonacci_rpc->call($num);
echo ' [.] ', $response, "\n";
