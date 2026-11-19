<?php

declare(strict_types=1);

namespace Prospecter\Core\Prospecting\Domain\Exception;

use Prospecter\Core\SharedKernel\Domain\Exception\BusinessRuleViolation;

/**
 * The address given as the website of an organization is no address of a site on the web.
 */
final class InvalidWebsite extends BusinessRuleViolation
{
    public static function of(string $value): self
    {
        return self::because(sprintf('"%s" is not the address of a website', $value), 'website');
    }
}
