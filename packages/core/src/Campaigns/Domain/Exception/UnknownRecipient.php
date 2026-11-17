<?php

declare(strict_types=1);

namespace Prospecter\Core\Campaigns\Domain\Exception;

use Prospecter\Core\SharedKernel\Domain\Exception\NotFound;
use Prospecter\Core\SharedKernel\Domain\PersonId;

/**
 * This contact is no recipient of the campaign: never added, or taken off already.
 */
final class UnknownRecipient extends NotFound
{
    public static function identifiedBy(PersonId $id): self
    {
        return new self(sprintf('Recipient %s not found', $id));
    }
}
