<?php

declare(strict_types=1);

namespace Prospecter\Core\PersonalData\Application\Person\ConsultPerson;

final readonly class ConsultPerson
{
    public function __construct(
        public string $readerId,
        public string $id,
    ) {
    }
}
