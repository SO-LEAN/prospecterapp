<?php

namespace App\Accounts\Input;

use Symfony\Component\Validator\Constraints as Assert;

final readonly class PasswordInput
{
    public function __construct(
        #[Assert\NotBlank]
        #[Assert\Length(min: 8, max: 4096)]
        public string $password = '',
    ) {
    }
}
