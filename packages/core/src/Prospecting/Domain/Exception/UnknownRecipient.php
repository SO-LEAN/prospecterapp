<?php

declare(strict_types=1);

namespace Prospecter\Core\Prospecting\Domain\Exception;

use Prospecter\Core\Prospecting\Domain\Model\ContactId;
use Prospecter\Core\SharedKernel\Domain\Exception\NotFound;

/**
 * This contact is not a recipient of the campaign that the exchange is logged in.
 */
final class UnknownRecipient extends NotFound
{
    public static function identifiedBy(ContactId $id): self
    {
        return new self(sprintf('Recipient %s not found', $id));
    }
}
