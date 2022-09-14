<?php

use Psr\Container\ContainerInterface;
use Slim\App;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

return function (App $app, ContainerInterface $container) {
  $app->get('/', function (Request $req, Response $res) {
    $res->getBody()->write('Hello world!');

    return $res;
  });
};
