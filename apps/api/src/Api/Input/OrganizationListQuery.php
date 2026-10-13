<?php

namespace App\Api\Input;

use Symfony\Component\Validator\Constraints as Assert;

final class OrganizationListQuery
{
    public function __construct(
        #[Assert\Positive]
        public int $page = 1,
        #[Assert\Length(max: 100)]
        public string $q = '',
    ) {
    }
}
