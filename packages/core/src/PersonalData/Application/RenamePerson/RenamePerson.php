<?php

declare(strict_types=1);

namespace Prospecter\Core\PersonalData\Application\RenamePerson;

use Prospecter\Core\PersonalData\Domain\PersonName;
use Prospecter\Core\SharedKernel\Domain\MemberId;
use Prospecter\Core\SharedKernel\Domain\PersonId;

final readonly class RenamePerson
{
    public function __construct(
        public MemberId $readerId,
        public PersonId $id,
        public PersonName $name,
    ) {
    }
}
