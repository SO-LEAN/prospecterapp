<?php

declare(strict_types=1);

namespace Prospecter\Core\Prospecting\Domain\Exception;

use Prospecter\Core\SharedKernel\Domain\Exception\NotFound;
use Prospecter\Core\SharedKernel\Domain\PersonId;

final class UnknownContact extends NotFound
{
    public static function identifiedBy(PersonId $id): self
    {
        return new self(sprintf('Contact %s not found', $id));
    }
}
