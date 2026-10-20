<?php

namespace App\SignIn\Command;

/**
 * The member an administrator registers from the console, as its arguments and options give them.
 */
final readonly class MemberToRegister
{
    /**
     * @param list<string> $roles
     */
    public function __construct(
        public string $administratorEmail,
        public string $email,
        public ?string $firstName,
        public ?string $lastName,
        public array $roles,
        public ?string $password,
    ) {
    }
}
