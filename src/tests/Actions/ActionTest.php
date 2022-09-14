<?php
declare(strict_types=1);

namespace Tests\Application\Actions;

use Hoanvv\App\Actions\Action;
use Hoanvv\App\Actions\ActionPayload;
use DateTimeImmutable;
use Psr\Http\Message\ResponseInterface as Response;
use Hoanvv\App\Factory\LoggerFactory;
use Hoanvv\Test\TestCase;
use Slim\App;

class ActionTest extends TestCase
{
    public function createApplication(): App
    {
        return (require __DIR__ . '/../../config/bootstrap.php');
    }

    public function testActionSetsHttpCodeInRespond()
    {
        $container = $this->app->getContainer();
        $logger = $container->get(LoggerFactory::class);

        $testAction = new class($logger) extends Action {
            public function __construct(LoggerFactory $loggerFactory) {
                parent::__construct($loggerFactory);
            }

            public function action(): Response
            {
                return $this->respond(
                    new ActionPayload(
                        202,
                        [
                            'willBeDoneAt' => (new DateTimeImmutable())->format(DateTimeImmutable::ATOM)
                        ]
                    )
                );
            }
        };

        $this->app->get('/test-action-response-code', $testAction);
        $request = $this->get('/test-action-response-code');

        $this->assertResponseStatus($request, 202);
    }

    public function testActionSetsHttpCodeRespondData()
    {
        $container = $this->app->getContainer();
        $logger = $container->get(LoggerFactory::class);

        $testAction = new class($logger) extends Action {
            public function __construct(LoggerFactory $loggerFactory) {
                parent::__construct($loggerFactory);
            }

            public function action(): Response
            {
                return $this->respondWithData(
                    [
                        'willBeDoneAt' => (new DateTimeImmutable())->format(DateTimeImmutable::ATOM)
                    ],
                    202
                );
            }
        };

        $this->app->get('/test-action-response-data', $testAction);
        $request = $this->get('/test-action-response-data');

        $this->assertResponseStatus($request, 202);
    }
}
