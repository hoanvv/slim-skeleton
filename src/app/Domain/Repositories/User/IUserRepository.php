<?php

declare(strict_types=1);

namespace Hoanvv\App\Domain\Repositories\User;

use Hoanvv\App\Domain\Models\User;
use Hoanvv\App\Domain\Models\UserNotFoundException;

interface IUserRepository
{
    /**
     * @return User[]
     */
    public function findAll(): array;

    /**
     * @param int $id
     * @return User
     * @throws UserNotFoundException
     */
    public function findUserOfId(int $id): User;
}
