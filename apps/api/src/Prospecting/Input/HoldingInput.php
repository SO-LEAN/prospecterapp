<?php

declare(strict_types=1);

namespace App\Prospecting\Input;

use Symfony\Component\Validator\Constraints as Assert;

final readonly class HoldingInput
{
    public function __construct(
        #[Assert\NotBlank]
        #[Assert\Uuid]
        public string $holdingId = '',
    ) {
    }
}
