<?php

declare(strict_types=1);

namespace Prospecter\Core\SharedKernel\Domain\Exception;

/**
 * The address is on the site of a known source, but none of the pages it recognizes (the page of a school or a
 * showcase page on LinkedIn, a search): it designates no person nor organization.
 */
final class UnrecognizedPage extends BusinessRuleViolation
{
    public static function on(string $host): self
    {
        return self::because(sprintf('This page of %s is none of the pages it designates a person or an organization by', $host), 'links', context: ['host' => $host]);
    }
}
