<?php

namespace Tests\Application\Actions;

use Hoanvv\Test\TestCase;
use Slim\App;
use Prophecy\PhpUnit\ProphecyTrait;
use Hoanvv\App\Actions\User\ListingAction;
use Hoanvv\App\Domain\Repositories\User\IUserRepository;
use Hoanvv\App\Domain\Models\User;
use Hoanvv\App\Actions\ActionPayload;
use Hoanvv\App\Domain\Repositories\User\UserRepository;

class ListUserTest extends TestCase
{
    use ProphecyTrait;
    
    public function createApplication(): App
    {
        return (require __DIR__ . '/../../config/bootstrap.php');
    }

    public function testListAllUserWithResponse()
    {
        $container = $this->app->getContainer();

        $userData = [
            new User(1, 'tri.huynh', 'Tri', 'Huynh', 27),
            new User(2, 'hoan.vo', 'Hoan', 'Vo', 27),
        ];

        $userRepoProphecy = $this->prophesize(UserRepository::class);
        $userRepoProphecy->findAll()->willReturn($userData)->shouldBeCalledOnce();

        $container->set(IUserRepository::class, $userRepoProphecy->reveal());

        // Mock ListingAction to /test-list-user
        $this->app->get('/test-list-user', ListingAction::class);
        // Mock a request
        $response = $this->get('/test-list-user');

        $payload = $response->getBody();

        // Mock a response
        $expectedPayload = new ActionPayload(200, $userData);

        $this->assertResponseStatus($response, 200);
        $this->assertSame(json_encode($payload), json_encode($expectedPayload));
    }
}
