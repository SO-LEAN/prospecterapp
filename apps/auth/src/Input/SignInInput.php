<?php

namespace App\Input;

final readonly class SignInInput
{
    public function __construct(
        public string $email = '',
        public string $password = '',
    ) {
    }
}
