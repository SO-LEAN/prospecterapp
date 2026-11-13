<?php

namespace App\Me\Input;

use Symfony\Component\Validator\Constraints as Assert;

final readonly class RemindersInput
{
    public function __construct(
        #[Assert\NotNull]
        public ?bool $enabled = null,
        #[Assert\NotNull]
        public ?int $minutesBefore = null,
    ) {
    }
}
