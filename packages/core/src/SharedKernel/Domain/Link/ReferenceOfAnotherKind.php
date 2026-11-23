<?php

declare(strict_types=1);

namespace Prospecter\Core\SharedKernel\Domain\Link;

use Prospecter\Core\SharedKernel\Domain\Exception\BusinessRuleViolation;

/**
 * The address is a known source's page that points to another kind than the record it is added to: a person's
 * profile on an organization, or a company's page on a person.
 */
final class ReferenceOfAnotherKind extends BusinessRuleViolation
{
    public static function from(KnownSource $source): self
    {
        return self::because(sprintf('A %s page designates %s', $source->value, Designated::Person === $source->designates() ? 'a person' : 'an organization'), 'links', context: ['source' => $source->value, 'designates' => $source->designates()->value]);
    }
}
