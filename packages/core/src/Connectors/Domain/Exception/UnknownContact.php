<?php

declare(strict_types=1);

namespace Prospecter\Core\Connectors\Domain\Exception;

use Prospecter\Core\Connectors\Domain\Model\ContactId;
use Prospecter\Core\SharedKernel\Domain\Exception\NotFound;

/**
 * The member does not read this contact unmasked. The contact may belong to another account, or not exist. The
 * member does not even learn whether it exists.
 */
final class UnknownContact extends NotFound
{
    public static function withId(ContactId $id): self
    {
        return new self(sprintf('Contact %s not found', $id->value));
    }
}
