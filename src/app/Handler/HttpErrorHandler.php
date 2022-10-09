<?php

namespace Hoanvv\App\Handler;

use Psr\Http\Message\ResponseInterface;
use Slim\Handlers\ErrorHandler;

class HttpErrorHandler extends ErrorHandler
{
    protected function respond(): ResponseInterface
    {
        $exception = $this->exception;
        // fix type of argument
        $code = (int) $exception->getCode();
        if ($code < 400 || $code > 999) {
            $code = 500;
        }
        $response = $this->responseFactory->createResponse($code);
        $response->getBody()->write((new JsonErrorRenderer())($exception, $this->displayErrorDetails));

        return $response->withAddedHeader('Content-Type', 'application/json');
    }
}
