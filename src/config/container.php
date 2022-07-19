<?php

use Hoanvv\App\Factory\DatabaseFactory;
use Hoanvv\App\Factory\LoggerFactory;
use Psr\Container\ContainerInterface;

return [
    LoggerFactory::class => function (ContainerInterface $container) {
        $settings = [
            'path' => dirname($_SERVER["SCRIPT_FILENAME"]) . '/../' . $_ENV['LOG_PATH'],
            'level' => (int)$_ENV['LOG_LEVEL'],
        ];

        return new LoggerFactory($settings);
    },

    DatabaseFactory::class => function (ContainerInterface $container) {
        return new DatabaseFactory();
    },
];
