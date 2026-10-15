<?php

declare(strict_types=1);

namespace Prospecter\Core\Prospecting\Domain\Exception;

use Prospecter\Core\SharedKernel\Domain\Exception\NotFound;
use Prospecter\Core\SharedKernel\Domain\MemberId;

final class UnknownProspector extends NotFound
{
    public static function identifiedBy(MemberId $id): self
    {
        return new self(sprintf('Prospector %s not found', $id));
    }
}
