<?php

namespace App\Prospecting\Input;

use Symfony\Component\Validator\Constraints as Assert;

final readonly class TimelineQuery
{
    public function __construct(
        #[Assert\Length(max: 200)]
        public ?string $after = null,
    ) {
    }
}
