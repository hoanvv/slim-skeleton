<?php

namespace Hoanvv\Test;

use Psr\Http\Message\ResponseInterface;

class TestResponse
{
    private ResponseInterface $originalResponse;

    private int $statusCode;

    private $body;

    public function __construct(ResponseInterface $response)
    {
        $this->originalResponse = $response;
        $this->statusCode = $response->getStatusCode();
        $this->body = $this->parseBody($response);
    }

    /**
     * @param ResponseInterface $response
     * @return mixed|string
     */
    protected function parseBody(ResponseInterface $response)
    {
        $bodyStream = $response->getBody();
        $bodyStream->rewind();
        $content = $bodyStream->getContents();

        return json_decode($content, true) ?? $content;
    }

    public function getStatusCode(): int
    {
        return $this->statusCode;
    }

    /**
     * @return mixed|string
     */
    public function getBody()
    {
        return $this->body;
    }

    public function getOriginalResponse(): ResponseInterface
    {
        return $this->originalResponse;
    }
}
