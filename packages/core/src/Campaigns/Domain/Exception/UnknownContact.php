<?php

declare(strict_types=1);

namespace Prospecter\Core\Campaigns\Domain\Exception;

use Prospecter\Core\SharedKernel\Domain\Exception\NotFound;
use Prospecter\Core\SharedKernel\Domain\Uuid;

/** This person is not a contact of the account, so none of its members tags them. */
final class UnknownContact extends NotFound
{
    public static function identifiedBy(Uuid $id): self
    {
        return new self(sprintf('Contact %s not found', $id));
    }
}
