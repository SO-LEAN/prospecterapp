<?php

namespace App\PersonalData\Input;

use Symfony\Component\Validator\Constraints as Assert;

final readonly class JobTitleInput
{
    public function __construct(
        #[Assert\Length(max: 100)]
        public ?string $jobTitle = null,
    ) {
    }
}
