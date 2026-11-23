<?php

declare(strict_types=1);

namespace Prospecter\Core\Prospecting\Domain\Exception;

use Prospecter\Core\SharedKernel\Domain\Exception\BusinessRuleViolation;

/**
 * The address given as an organization's website is not the address of a website.
 */
final class InvalidWebsite extends BusinessRuleViolation
{
    public static function of(string $value): self
    {
        return self::because(sprintf('"%s" is not the address of a website', $value), 'website');
    }
}
