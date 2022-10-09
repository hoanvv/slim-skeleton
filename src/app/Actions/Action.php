<?php

declare(strict_types=1);

namespace Hoanvv\App\Actions;

use App\Domain\DomainException\DomainRecordNotFoundException;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Hoanvv\App\Factory\LoggerFactory;
use Slim\Exception\HttpBadRequestException;
use Slim\Exception\HttpNotFoundException;

abstract class Action
{
    protected LoggerFactory $logger;

    protected Request $request;

    protected Response $response;

    public int $statusCode = 400;
    protected array $args;

    public function __construct(LoggerFactory $logger)
    {
        $this->logger = $logger;
    }

    /**
     * @throws HttpNotFoundException
     * @throws HttpBadRequestException
     */
    public function __invoke(Response $response)
    {
        // point to function in class, no need to use __invoke() method
        // keep a reference to the response
        // $this->request = $request;
        $this->response = $response;
        // $this->args = $args;

        // try {
        //     return $this->action();
        // } catch (DomainRecordNotFoundException $e) {
        //     throw new HttpNotFoundException($this->request, $e->getMessage());
        // }
    }

    /**
     * @throws DomainRecordNotFoundException
     * @throws HttpBadRequestException
     */
    abstract protected function action(): Response;

    /**
     * @return array|object
     */
    protected function getFormData()
    {
        return $this->request->getParsedBody();
    }

    /**
     * @return mixed
     * @throws HttpBadRequestException
     */
    protected function resolveArg(string $name)
    {
        if (!isset($this->args[$name])) {
            throw new HttpBadRequestException($this->request, "Could not resolve argument `{$name}`.");
        }

        return $this->args[$name];
    }

    /**
     * @param array|object|null $data
     */
    protected function respondWithData($data = null, int $statusCode = 200, $response = null): Response
    {
        // make sure the response is initialized before used
        if ($response) {
            call_user_func('Hoanvv\App\Actions\Action::__invoke', $response);
        }

        $payload = new ActionPayload($statusCode, $data);

        return $this->respond($payload);
    }

    protected function respond(ActionPayload $payload): Response
    {
        $json = json_encode($payload, JSON_PRETTY_PRINT);
        $this->response->getBody()->write($json);

        return $this->response
            ->withHeader('Content-Type', 'application/json')
            ->withStatus($payload->getStatusCode());
    }
}
