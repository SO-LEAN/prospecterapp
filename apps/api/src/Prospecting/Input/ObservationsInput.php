<?php

namespace App\Prospecting\Input;

use Symfony\Component\Validator\Constraints as Assert;

final readonly class ObservationsInput
{
    public function __construct(
        #[Assert\Length(max: 255)]
        public ?string $observations = null,
    ) {
    }
}
