<?php

declare(strict_types=1);

namespace Prospecter\Core\PersonalData\Domain;

use Prospecter\Core\SharedKernel\Domain\NotFound;
use Prospecter\Core\SharedKernel\Domain\PersonId;

final class UnknownPerson extends NotFound
{
    public function __construct(PersonId $id)
    {
        parent::__construct(sprintf('Person %s not found', $id));
    }
}
