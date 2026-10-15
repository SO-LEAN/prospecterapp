<?php

declare(strict_types=1);

namespace Prospecter\Core\Domain\User;

final readonly class PersonName
{
    public function __construct(
        public ?string $firstName = null,
        public ?string $lastName = null,
    ) {
    }
}
