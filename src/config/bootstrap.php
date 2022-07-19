<?php

require_once __DIR__ . '/../vendor/autoload.php';

use Hoanvv\App\Factory\ContainerFactory;
use Slim\App;

$rootDir = dirname(__DIR__);
$container = (new ContainerFactory($rootDir))->createInstance();

return $container->get(App::class);
