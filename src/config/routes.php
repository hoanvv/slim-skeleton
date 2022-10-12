<?php

use Psr\Container\ContainerInterface;
use Slim\App;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Hoanvv\App\Actions\User\ListingAction;
use Slim\Routing\RouteCollectorProxy;
use Hoanvv\App\Middleware\AuthMiddleware;
use Hoanvv\App\Mail\MailController;

return function (App $app, ContainerInterface $container) {
  $app->get('/', function (Request $req, Response $res) {
    $res->getBody()->write('Hello world!');

    return $res;
  });

  // $app->get('/list-user', [ListingAction::class, 'registerUser']);
  // $app->get('/list-user', ListingAction::class);

  /**
   * CREATE CRUD API
   */
  // UNAUTHENTICATED API
  // Register new users
  $app->post('/register', [ListingAction::class, 'registerUser']);

  // AUTHENTICATED API
  $app->group('/v1', function (RouteCollectorProxy $group) {
    // Get info of users
    $group->get('/info/{user_id:[0-9]+}', [ListingAction::class, 'findUser']);
    // Update user data
    $group->put('/update/{user_id:[0-9]+}', [ListingAction::class, 'updateUser']);
    // Delete user
    $group->delete('/delete/{user_id:[0-9]+}', [ListingAction::class, 'deleteUser']);
  })->add(AuthMiddleware::class);


  /**
   * RabitMQ services
   */
  // Send msg to queues
  $app->post('/mail/sender', [MailController::class, 'sendQueueMessage'])->setName('rabbit.sender');
  // Publish message to exchanges
  $app->post('/mail/publish', [MailController::class, 'publishMessage']);
  // Routing message with binding key
  $app->post('/mail/routing', [MailController::class, 'routingMessage']);
  // Filter message by topic
  $app->post('/mail/topic', [MailController::class, 'topicMessage']);
};
