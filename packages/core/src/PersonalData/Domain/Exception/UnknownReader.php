<?php

declare(strict_types=1);

namespace Prospecter\Core\PersonalData\Domain\Exception;

use Prospecter\Core\SharedKernel\Domain\Exception\NotFound;
use Prospecter\Core\SharedKernel\Domain\MemberId;

final class UnknownReader extends NotFound
{
    public static function identifiedBy(MemberId $id): self
    {
        return new self(sprintf('Reader %s not found', $id));
    }
}
