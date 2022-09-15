<?php

declare(strict_types=1);

namespace Hoanvv\App\Domain\Models;

use JsonSerializable;

class User implements JsonSerializable
{
    private ?int $id;

    private string $username;

    private string $firstName;

    private string $lastName;

    private int $age;

    public function __construct(?int $id, string $username, string $firstName, string $lastName, int $age)
    {
        $this->id = $id;
        $this->username = strtolower($username);
        $this->firstName = ucfirst($firstName);
        $this->lastName = ucfirst($lastName);
        $this->age = $age;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getUsername(): string
    {
        return $this->username;
    }

    public function getFirstName(): string
    {
        return $this->firstName;
    }

    public function getLastName(): string
    {
        return $this->lastName;
    }

    public function getAge(): int
    {
        return $this->age;
    }

    #[\ReturnTypeWillChange]
    public function jsonSerialize(): array
    {
        return [
            'id' => $this->id,
            'username' => $this->username,
            'firstName' => $this->firstName,
            'lastName' => $this->lastName,
            'age' => $this->age,
        ];
    }
}
