<?php

declare(strict_types=1);

namespace Prospecter\Core\SharedKernel\Domain\Exception;

use Prospecter\Core\SharedKernel\Domain\KnownSource;

/**
 * The address is a page of a known source that designates another kind than the one it is noted on: a profile of a
 * person on an organization, the page of a company on a person.
 */
final class ReferenceOfAnotherKind extends BusinessRuleViolation
{
    public static function from(KnownSource $source): self
    {
        return self::because(sprintf('A page of %s designates a %s', $source->value, $source->designates()->value), 'links', context: ['source' => $source->value, 'designates' => $source->designates()->value]);
    }
}
