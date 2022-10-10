<?php

declare(strict_types=1);

namespace Hoanvv\App\Middleware;

use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\RequestHandlerInterface as RequestHandler;
use Psr\Http\Message\ResponseInterface as Response;
use Slim\Routing\RouteContext;
use Hoanvv\App\Database\IMasterDatabase;
use Psr\Http\Server\MiddlewareInterface;

class AuthMiddleware implements MiddlewareInterface
{
    private $database;

    public function __construct(IMasterDatabase $master)
    {   
        $this->database = $master;
    }

    public function process(Request $request, RequestHandler $handler): Response
    {
        $routeContext = RouteContext::fromRequest($request);
        $route = $routeContext->getRoute();
        $user_id = $route->getArgument('user_id');
        $result = $this->database->query("SELECT * FROM users where id = $user_id LIMIT 1")->fetch();
        if ($result) {
            // if authorized, save the current user in the request
            $request = $request->withAttribute('userData', $result);
            $response = $handler->handle($request);
            return $response;
        } else {
            // throw error if request is not authorized
            throw new \Slim\Exception\HttpUnauthorizedException($request, 'Unauthorized request');
        }
    }
}
