<?php

namespace Tests\Application\Repositories;

use Hoanvv\Test\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;
use Slim\App;
use Hoanvv\App\Domain\Models\User;
use Hoanvv\App\Domain\Repositories\User\IUserRepository;
use Hoanvv\App\Domain\Repositories\User\UserRepository;
use Hoanvv\App\Domain\Models\UserNotFoundException;

class UserRepositoryTest extends TestCase
{
    use ProphecyTrait;

    public function createApplication(): App
    {
        return (require __DIR__ . '/../../config/bootstrap.php');
    }
    public function setUpData()
    {
        // do something
    }

    public function testFindAllUsers()
    {
        $container = $this->app->getContainer();

        $userData = [
            new User(1, 'tri.huynh', 'Tri', 'Huynh', 27),
            new User(2, 'hoan.vo', 'Hoan', 'Vo', 27),
        ];

        $userRepoProphecy = $this->prophesize(UserRepository::class);
        $userRepoProphecy->findAll()->willReturn($userData)->shouldBeCalledOnce();

        $container->set(IUserRepository::class, $userRepoProphecy->reveal());
        $userRepository = $container->get(IUserRepository::class);
        $users = $userRepository->findAll();

        $this->assertIsArray($users);
        $this->assertSame($users, $userData);
    }

    public function testFindUserOfId()
    {
        $container = $this->app->getContainer();

        $userData = new User(100, 'tri.huynh', 'Tri', 'Huynh', 27);

        $userRepoProphecy = $this->prophesize(UserRepository::class);
        $userRepoProphecy->findUserOfId(100)->willReturn($userData)->shouldBeCalledOnce();

        $container->set(IUserRepository::class, $userRepoProphecy->reveal());
        $userRepository = $container->get(IUserRepository::class);
        $user = $userRepository->findUserOfId(100);

        $this->assertSame($user, $userData);
    }

    public function testUserNotFound()
    {
        $container = $this->app->getContainer();
        $userRepo = $container->get(IUserRepository::class);

        $this->expectException(UserNotFoundException::class);
        $userRepo->findUserOfId(101);
    }
}
