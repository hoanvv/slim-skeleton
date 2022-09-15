<?php

declare(strict_types=1);

namespace Hoanvv\App\Domain\Repositories\User;

use Hoanvv\App\Domain\Models\User; 
use Hoanvv\App\Domain\Models\UserNotFoundException;

class UserRepository implements IUserRepository
{ 
    /**
     * @var User[]
     */
    private array $users;

    /**
     * @param User[]|null $users
     */
    public function __construct(array $users = null)
    {
        $this->users = $users ?? [
            1 => new User(1, 'bill.gates', 'Bill', 'Gates', 50),
            2 => new User(2, 'steve.jobs', 'Steve', 'Jobs', 51),
            3 => new User(3, 'mark.zuckerberg', 'Mark', 'Zuckerberg', 35),
            4 => new User(4, 'evan.spiegel', 'Evan', 'Spiegel', 39),
            5 => new User(5, 'jack.dorsey', 'Jack', 'Dorsey', 68),
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function findAll(): array
    {
        return array_values($this->users);
    }

    /**
     * {@inheritdoc}
     */
    public function findUserOfId(int $id): User
    {
        if (!isset($this->users[$id])) {
            throw new UserNotFoundException();
        }

        return $this->users[$id];
    }
}
