<?php

declare(strict_types=1);

namespace Prospecter\Core\Connectors\Domain\Exception;

use Prospecter\Core\SharedKernel\Domain\Exception\NotFound;
use Prospecter\Core\SharedKernel\Domain\OrganizationId;

/**
 * No organization the account prospects, of another account or none: the member does not even learn that it exists.
 */
final class UnknownOrganization extends NotFound
{
    public static function withId(OrganizationId $id): self
    {
        return new self(sprintf('Unknown organization %s', $id->value));
    }
}
