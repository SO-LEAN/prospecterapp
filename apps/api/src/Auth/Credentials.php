<?php

namespace App\Auth;

use Doctrine\ORM\Mapping as ORM;

/**
 * What a member signs in with: a login email, unique on the whole platform, and a password. Authentication
 * is not the business of the core: it lives here, apart from it, so that it can move to a dedicated
 * authorization server later (ADR 0011, 0032). The login email is an access identifier, not a copy of the
 * profile kept by PersonalData.
 */
#[ORM\Entity]
#[ORM\Table(name: 'credentials')]
class Credentials
{
    public function __construct(
        #[ORM\Id]
        #[ORM\Column(type: 'guid')]
        private readonly string $memberId,
        #[ORM\Column(length: 255, unique: true)]
        private string $email,
        #[ORM\Column(length: 255, nullable: true)]
        private ?string $passwordHash = null,
    ) {
    }

    public function memberId(): string
    {
        return $this->memberId;
    }

    public function email(): string
    {
        return $this->email;
    }

    public function passwordHash(): ?string
    {
        return $this->passwordHash;
    }

    public function changeEmail(string $email): void
    {
        $this->email = $email;
    }

    public function changePasswordHash(string $passwordHash): void
    {
        $this->passwordHash = $passwordHash;
    }
}
