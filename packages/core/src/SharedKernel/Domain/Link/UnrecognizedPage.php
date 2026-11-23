<?php

declare(strict_types=1);

namespace Prospecter\Core\SharedKernel\Domain\Link;

use Prospecter\Core\SharedKernel\Domain\Exception\BusinessRuleViolation;

/**
 * The address is on a known source's site but is not one of the pages that Prospecter recognizes there (a school's
 * page or a showcase page on LinkedIn, a search). It points to no person and no organization.
 */
final class UnrecognizedPage extends BusinessRuleViolation
{
    public static function on(string $host): self
    {
        return self::because(sprintf('This page of %s is none of the pages it designates a person or an organization by', $host), 'links', context: ['host' => $host]);
    }
}
