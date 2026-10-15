<?php

namespace App\Auth;

use Doctrine\ORM\Mapping as ORM;

/**
 * The secret a user signs in with. Authentication is not the business of the use cases: it lives
 * here, apart from the core, so that it can move to a dedicated authorization server later.
 */
#[ORM\Entity]
#[ORM\Table(name: 'credentials')]
class Credentials
{
    public function __construct(
        #[ORM\Id]
        #[ORM\Column(type: 'guid')]
        private readonly string $userId,
        #[ORM\Column(length: 255)]
        private string $passwordHash,
    ) {
    }

    public function userId(): string
    {
        return $this->userId;
    }

    public function passwordHash(): string
    {
        return $this->passwordHash;
    }

    public function changePasswordHash(string $passwordHash): void
    {
        $this->passwordHash = $passwordHash;
    }
}
