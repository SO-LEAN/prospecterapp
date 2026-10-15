<?php

namespace App\Input;

use Symfony\Component\Validator\Constraints as Assert;

final readonly class LoginEmailInput
{
    public function __construct(
        #[Assert\NotBlank]
        #[Assert\Email]
        #[Assert\Length(max: 255)]
        public string $email = '',
    ) {
    }
}
