<?php

declare(strict_types=1);

namespace Prospecter\Core\PersonalData\Application\ConsultPersons;

use Prospecter\Core\SharedKernel\Domain\MemberId;
use Prospecter\Core\SharedKernel\Domain\PersonId;

final readonly class ConsultPersons
{
    /**
     * @param list<PersonId> $ids
     */
    public function __construct(
        public MemberId $readerId,
        public array $ids,
    ) {
    }
}
