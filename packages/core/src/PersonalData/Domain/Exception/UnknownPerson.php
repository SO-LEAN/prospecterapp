<?php

declare(strict_types=1);

namespace Prospecter\Core\PersonalData\Domain\Exception;

use Prospecter\Core\PersonalData\Domain\Model\PersonId;
use Prospecter\Core\SharedKernel\Domain\Exception\NotFound;

final class UnknownPerson extends NotFound
{
    public static function identifiedBy(PersonId $id): self
    {
        return new self(sprintf('Person %s not found', $id));
    }
}
