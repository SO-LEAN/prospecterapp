<?php

namespace App\Api\Input;

use Symfony\Component\Validator\Constraints as Assert;

final readonly class UserNameInput
{
    public function __construct(
        #[Assert\NotBlank]
        #[Assert\Length(max: 50)]
        public string $userName = '',
    ) {
    }
}
