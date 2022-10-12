<?php

use Hoanvv\App\Factory\DatabaseFactory;
use Hoanvv\App\Factory\LoggerFactory;
use Psr\Container\ContainerInterface;
use Hoanvv\App\Database\IMasterDatabase;
use Hoanvv\App\Database\MasterDatabase;
use Hoanvv\App\Mail\RabbitMqConnection;

// Use mysql as default database
$driver = $_ENV['driver'] ?? 'mysql';
$dbObject = $driver == 'mysql' ? DI\autowire(MasterDatabase::class) : DI\autowire(MasterDatabase::class);

return [
    LoggerFactory::class => function (ContainerInterface $container) {
        $settings = [
            'path' => dirname($_SERVER['SCRIPT_FILENAME']) . '/../' . $_ENV['LOG_PATH'],
            'level' => (int) $_ENV['LOG_LEVEL'],
        ];

        return new LoggerFactory($settings);
    },

    DatabaseFactory::class => function (ContainerInterface $container) {
        return new DatabaseFactory();
    },
    // 2 ways to create database connection in container
    // IMasterDatabase::class => function (ContainerInterface $container) {
    //     $database = $_ENV['driver'] ?? 'MySQL';
    //     // default is MySQL
    //     if ($database == 'MySQL') {
    //         return new MasterDatabase();
    //         // return DI\autowire(MasterDatabase::class);
    //     }
    // },
    IMasterDatabase::class => $dbObject,
    
    // add rabbit connnection as a service
    RabbitMqConnection::class => function (ContainerInterface $container) {
        return new RabbitMqConnection(
            [
                'hosts' => [
                    [
                        'host' => 'rabbitmq',
                        'port' => 5672,
                        'user' => 'guest',
                        'password' => 'guest',
                        'vhost' => '/',
                    ]
                ]
            ]
        );
    }
];
