<?php

/**
 *
 * Copyright (c) 2021, Bergfreunde GmbH. All rights reserved.
 *
 * Project:    Bergfreunde Shop
 * Created:    2021-12-28
 *
 * @package common
 * @subpackage Bergfreunde
 * @author Benjamin I. Williams
 */

declare(strict_types=1);

namespace Hoanvv\App\Mail;

use Exception;
use PhpAmqpLib\Channel\AbstractChannel;
use PhpAmqpLib\Connection\AbstractConnection;
use PhpAmqpLib\Connection\AMQPSSLConnection;
use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;
use PhpAmqpLib\Wire\AMQPTable;

class RabbitMqConnection
{
    const WAIT_BEFORE_RECONNECT = 100000; // in uSeconds: = 1/10 second

    /** @var \PhpAmqpLib\Connection\AbstractConnection|null */
    public $connection = null;

    /** @var \PhpAmqpLib\Channel\AMQPChannel|null */
    protected $channel = null;

    protected array $defaultHeaders = [];

    /**
     * constructor for the AmqpClient class
     *
     * @throws Exception
     */
    public function __construct(array $config = [])
    {
        if (!empty($config)) {
            if (!isset($config['hosts'])) {
                throw new Exception('Missing hosts entry in configuration');
            }
        }
        $this->connect($config);
    }

    /**
     * closes down the RabbitMQ connection
     *
     * @return void
     * @throws Exception
     */
    public function __destruct()
    {
        if ($this->channel instanceof AbstractChannel) {
            $this->channel->close();
            $this->channel = null;
        }

        if ($this->connection instanceof AbstractConnection) {
            self::cleanupConnection($this->connection);
            $this->connection = null;
        }
    }

    /**
     * connect to a group of one more more AMQP servers
     *
     * @param array $config An array which contains one or more hosts,
     *                      e.g. [ "hosts" => ["host":"","port":5672,"user":"","password":"", "vhost":""] ]
     * @param float $maxConnectionWait
     * @return bool
     * @throws \Bergfreunde\Queue\Exception
     */
    public function connect(array $config, float $maxConnectionWait = 3.0)
    {
        if (!isset($config['hosts']) || !is_array($config['hosts']) || count($config['hosts']) == 0) {
            throw new Exception('Missing hosts entry in configuration');
        }

        $lastException = null;
        $startTime = microtime(true);

        while (true) {
            try {
                $connection = null;
                $channel = null;

                // see if we've expired the specified maxConnectionWait
                $currentWait = microtime(true) - $startTime;
                if ($currentWait >= $maxConnectionWait) {
                    if ($lastException === null) {
                        $lastException = new Exception('AMQP Connection Timeout');
                    }
                    break;
                }

                shuffle($config['hosts']);

                if (empty($config['useTls'])) {
                    $connection = AMQPStreamConnection::create_connection($config['hosts']);
                } else {
                    $connection = AMQPSSLConnection::create_connection($config['hosts'], ['ssl_options' => ['verify_peer' => true]]);
                }
                $channel = $connection->channel();
                $channel->confirm_select();
                // When a messge is acknowledged
                $channel->set_ack_handler(
                    function (AMQPMessage $message) {
                        // code when message is confirmed
                        // echo "Message ACKED" . PHP_EOL;
                    }
                );
                // When a messge is negative-acknowledged
                $channel->set_nack_handler(
                    function (AMQPMessage $message) {
                        // code when message is nack-ed
                        // echo "Message NACKED " . PHP_EOL;
                    }
                );

                $this->connection = $connection;
                $this->channel = $channel;

                return true;
            } catch (Exception $e) {
                $lastException = $e;

                self::cleanupConnection($connection);
            }

            usleep(self::WAIT_BEFORE_RECONNECT);
        }
        throw self::convertException($lastException);
    }

    /**
     * send message to a RabbitMQ exchange
     *
     * @param mixed $data data to send
     * @param string $exchange receiving exchange
     * @param array $headers
     * @throws Exception
     */
    public function send($data, string $exchange, array $headers = [])
    {
        if ($this->connection == null || $this->channel == null) {
            throw new Exception('AmqpClient::send() invoked without active connection/channel');
        }

        if (!$exchange) {
            $queue = $_ENV['rabbitmq_queue'] ?? 'default_channel';
            if ($queue == 'default_channel') {
                // Declare Message durability
                $this->channel->queue_declare($queue, false, true, false, false);
            };
        }
        // Publish/Broadcast
        if ($exchange == 'rabbit_logs') {
            $this->channel->exchange_declare($exchange, 'fanout', false, false, false);
        } 
        // Direct to correct queue
        else if ($exchange == 'rabbit_direct') {
            $this->channel->exchange_declare($exchange, 'direct', false, false, false);
            $bindingKey = $data['binding_key'];
        } 
        // Filter message using * and #
        else if ($exchange == 'rabbit_topic') {
            $this->channel->exchange_declare($exchange, 'topic', false, false, false);
            $routingKey = $data['routing_key'];
        }

        try {
            // merge headers with default headers, allowing caller to override default headers
            $headers = array_merge($this->getDefaultHeaders(), $headers);
            $props = $this->getMessageProps();

            // create an AMQP message and publish it
            $msg = $this->createAMQPMessage($data, $props, $headers);
            if (!$exchange) {
                $status = $this->channel->basic_publish($msg, $exchange, $queue);
            } else if ($exchange == 'rabbit_logs') {
                // Publish the message to the exchange
                $status = $this->channel->basic_publish($msg, $exchange);
            } else if ($exchange == 'rabbit_direct') {
                // Routing the message to the exchange
                $status = $this->channel->basic_publish($msg, $exchange, $bindingKey);
            } else if ($exchange == 'rabbit_topic') {
                // Filter the message by topic
                $status = $this->channel->basic_publish($msg, $exchange, $routingKey);
            }
            $this->channel->wait_for_pending_acks();
            // $this->channel->wait_for_pending_acks_returns();

            return $status;
        } catch (\Throwable $e) {
            // the above code throws various kinds of exceptions-- convert them
            // all to an Exception
            throw self::convertException($e);
        }
    }

    /**
     * return default AMQP message headers as an array
     *
     * @return array an array containing default AMQP message headers
     */
    public function getDefaultHeaders(): array
    {
        return $this->defaultHeaders;
    }

    /**
     * return client config
     * @codeCoverageIgnore
     * @throws Exception
     */
    protected function initConfig(string $configName, array $config = [], array $headers = []): array
    {
        if (empty($config)) {
            $config = Config::load($configName);
            $config = $config->toArray();
        }

        if (!empty($headers)) {
            $this->setDefaultHeaders($headers);
        }

        return $config;
    }

    /**
     * set the default AMQP messsage headers; these can be amended later when calling send()
     *
     * @return array returns the newly set headers as an array
     */
    public function setDefaultHeaders(array $headers): array
    {
        $this->defaultHeaders = $headers;
        return $this->defaultHeaders;
    }

    /**
     * set the default AMQP messsage properties
     *
     * @return array an array containing the message properties
     */
    protected function getMessageProps(): array
    {
        return [
            'content_type'  => 'application/json',
            // 'delivery_mode' => AMQPMessage::DELIVERY_MODE_PERSISTENT
        ];
    }

    /**
     * create an AMQP message object with the specified data, properties, and headers
     *
     * @param string|array $data a string or array containing the message payload; arrays will be automatically converted to JSON format
     * @param array $props an array containing the message properties
     * @param array $headers an array containing the message headers
     * @return AMQPMessage an AMQPMessage object
     */
    protected function createAMQPMessage($data, array $props, array $headers): AMQPMessage
    {
        if (is_array($data)) {
            $data = json_encode($data);
        }

        $msg = new AMQPMessage($data, $props);
        $msg->set('application_headers', new AMQPTable($headers));
        return $msg;
    }

    /**
     * clean up an AMPQ connection
     *
     * @returns void
     */
    protected static function cleanupConnection($connection)
    {
        // cleanup the connection and mask any exceptions
        try {
            if ($connection !== null) {
                $connection->close();
            }
        } catch (\ErrorException $e) {
        }
    }


    /**
     * convert any exception to an Exception object with the same error message and code
     *
     * @return Exception an Exception object
     */
    protected static function convertException(\Throwable $e)
    {
        return new Exception($e->getMessage(), $e->getCode());
    }
}
