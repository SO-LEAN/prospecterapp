<?php

declare(strict_types=1);

namespace Prospecter\Core\Campaigns\Domain\Exception;

use Prospecter\Core\Campaigns\Domain\Model\ContactId;
use Prospecter\Core\SharedKernel\Domain\Exception\NotFound;

/** This contact is not a recipient of the campaign. They were never added, or were already removed. */
final class UnknownRecipient extends NotFound
{
    public static function identifiedBy(ContactId $id): self
    {
        return new self(sprintf('Recipient %s not found', $id));
    }
}
