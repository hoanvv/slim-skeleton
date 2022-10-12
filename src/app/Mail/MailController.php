<?php

namespace Hoanvv\App\Mail;

use Psr\Http\Message\ResponseInterface as Response;
use Hoanvv\App\Actions\Action;
use Hoanvv\App\Mail\RabbitMqConnection;
use \DateTimeImmutable;

class MailController extends Action
{
    protected $connection;
    protected $channel;
    protected $rabbitMq;
    protected $currentDateTime;

    public function __construct(RabbitMqConnection $rabbitMq)
    {
        $this->rabbitMq = $rabbitMq;
        $this->currentDateTime = (new DateTimeImmutable())->format('Y-m-d H-i-s');
    }

    protected function action(): Response
    {
        //do something here
        return $this->respondWithData([
            'message' => '',
        ]);
    }
    /**
     * send message API
     * 
     * Send message to a queue in RabbitMQ server
     * This API includes the following cases:
     * 
     * 1. Send messages to queue
     * 2. Acknowledge message to store if the server or connection is down
     * 
     * Payload: [ 'msg' => $message, 'delay' => $delay] 
     */
    public function sendQueueMessage($request, $response)
    {
        // $this->rabbitMq->connect([]);
        $params = $this->getFormData($request);
        $params['msg'] .= ' ' . $this->currentDateTime;

        $this->rabbitMq->send($params, '');
        return $this->respondWithData([
            'message' => $params,
        ], 200, $response);
    }

    /**
     * Publish a message to an exchange
     * 
     * Payload: ['msg' => $message] 
     */
    public function publishMessage($request, $response)
    {
        $params = $this->getFormData($request);
        $params['msg'] .= ' ' . $this->currentDateTime;

        $this->rabbitMq->send($params, 'rabbit_logs');
        return $this->respondWithData([
            'message' => $params,
        ], 200, $response);
    }

    /**
     * Routing a message to a queue
     * 
     * Payload: [ 'msg' => $message, 'binding_key' => $bindingKey] 
     */
    public function routingMessage($request, $response)
    {
        $params = $this->getFormData($request);
        $params['msg'] .= ' ' . $this->currentDateTime;
        $params['binding_key'] = in_array($params['binding_key'], ['info', 'warning', 'error']) ? $params['binding_key'] : 'none';

        $this->rabbitMq->send($params, 'rabbit_direct');
        return $this->respondWithData([
            'message' => $params,
        ], 200, $response);
    }

    /**
     * Filter a message by topic
     * The messages will be sent with a routing key that consists of THREE words (TWO dots)
     * 
     * * (star) can substitute for exactly one word.
     * # (hash) can substitute for zero or more words.
     * 
     * Payload: [ 'msg' => $message, 'routing_key' => $routingKey] 
     */
    public function topicMessage($request, $response)
    {
        $params = $this->getFormData($request);
        $params['msg'] .= ' ' . $this->currentDateTime;

        $this->rabbitMq->send($params, 'rabbit_topic');
        return $this->respondWithData([
            'message' => $params,
        ], 200, $response);
    }
}
