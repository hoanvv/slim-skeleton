<?php

use Psr\Container\ContainerInterface;
use Hoanvv\App\Domain\Repositories\User\UserRepository;
use Hoanvv\App\Domain\Repositories\User\IUserRepository;

return [
    IUserRepository::class => DI\autowire(UserRepository::class),
];
