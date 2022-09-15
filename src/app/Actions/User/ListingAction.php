<?php

declare(strict_types=1);

namespace Hoanvv\App\Actions\User;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Hoanvv\App\Actions\Action;
use Hoanvv\App\Domain\Repositories\User\IUserRepository;
use Hoanvv\App\Factory\LoggerFactory;

class ListingAction extends Action
{
    /**
     * @var IUserRepository
     */
    protected $userRepository;

    protected LoggerFactory $logger;

    public function __construct(IUserRepository $userRepository, LoggerFactory $logger)
    {
        $this->userRepository = $userRepository;
        parent::__construct($logger);
    }

    protected function action(): Response
    {
        $users = $this->userRepository->findAll();

        return $this->respondWithData($users);
    }
}
