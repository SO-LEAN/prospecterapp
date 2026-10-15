<?php

declare(strict_types=1);

namespace Prospecter\Core\PersonalData\Application\ConsultPerson;

use Prospecter\Core\SharedKernel\Domain\MemberId;
use Prospecter\Core\SharedKernel\Domain\PersonId;

final readonly class ConsultPerson
{
    public function __construct(
        public MemberId $readerId,
        public PersonId $id,
    ) {
    }
}
