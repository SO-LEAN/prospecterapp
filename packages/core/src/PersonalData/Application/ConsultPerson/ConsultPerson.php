<?php

declare(strict_types=1);

namespace Prospecter\Core\PersonalData\Application\ConsultPerson;

final readonly class ConsultPerson
{
    public function __construct(
        public string $readerId,
        public string $id,
    ) {
    }
}
