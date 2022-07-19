<?php

use Hoanvv\App\Handler\HttpErrorHandler;
use Hoanvv\App\Handler\ShutdownHandler;
use Slim\App;
use Slim\Factory\ServerRequestCreatorFactory;

return function (App $app) {
    // Set that to your needs
    $displayErrorDetails = IS_DEBUG();
    $callableResolver = $app->getCallableResolver();
    $responseFactory = $app->getResponseFactory();
    $serverRequestCreator = ServerRequestCreatorFactory::create();
    $request = $serverRequestCreator->createServerRequestFromGlobals();

    $errorHandler = new HttpErrorHandler($callableResolver, $responseFactory);
    $shutdownHandler = new ShutdownHandler($request, $errorHandler, $displayErrorDetails);
    register_shutdown_function($shutdownHandler);

    // Add Routing Middleware
    $app->addRoutingMiddleware();
    // Add Body Parsing Middleware
    $app->addBodyParsingMiddleware();
    // Add Error Handling Middleware
    // NOTE: the ErrorMiddleware should be in the end.
    // If there are some middlewares behind this middleware,
    // there can be some unexpected issues
    $errorMiddleware = $app->addErrorMiddleware($displayErrorDetails, false, false);
    $errorMiddleware->setDefaultErrorHandler($errorHandler);
};
