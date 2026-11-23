<?php

declare(strict_types=1);

namespace Prospecter\Core\Prospecting\Domain\Exception;

use Prospecter\Core\Prospecting\Domain\Model\ContactId;
use Prospecter\Core\SharedKernel\Domain\Exception\NotFound;

final class UnknownContact extends NotFound
{
    public static function identifiedBy(ContactId $id): self
    {
        return new self(sprintf('Contact %s not found', $id));
    }
}
