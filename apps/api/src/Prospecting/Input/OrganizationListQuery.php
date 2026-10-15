<?php

namespace App\Prospecting\Input;

use Symfony\Component\Validator\Constraints as Assert;

final readonly class OrganizationListQuery
{
    public function __construct(
        #[Assert\Positive]
        public int $page = 1,
        #[Assert\Length(max: 100)]
        public string $q = '',
    ) {
    }
}
