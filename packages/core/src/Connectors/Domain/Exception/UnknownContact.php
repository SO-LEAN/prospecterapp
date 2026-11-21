<?php

declare(strict_types=1);

namespace Prospecter\Core\Connectors\Domain\Exception;

use Prospecter\Core\SharedKernel\Domain\Exception\NotFound;
use Prospecter\Core\SharedKernel\Domain\PersonId;

/**
 * No contact the member reads in clear, of another account or none: they do not even learn that it exists.
 */
final class UnknownContact extends NotFound
{
    public static function withId(PersonId $id): self
    {
        return new self(sprintf('Unknown contact %s', $id->value));
    }
}
