<?php

namespace App\Prospecting\Input;

use Symfony\Component\Validator\Constraints as Assert;

final readonly class OrganizationListQuery
{
    public function __construct(
        #[Assert\Length(max: 100)]
        public string $q = '',
        #[Assert\Length(max: 200)]
        public ?string $after = null,
    ) {
    }
}
