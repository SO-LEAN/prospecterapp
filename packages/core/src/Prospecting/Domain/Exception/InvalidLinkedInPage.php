<?php

declare(strict_types=1);

namespace Prospecter\Core\Prospecting\Domain\Exception;

use Prospecter\Core\SharedKernel\Domain\Exception\BusinessRuleViolation;

/**
 * The address given as the LinkedIn page of an organization is no page of a company on LinkedIn.
 */
final class InvalidLinkedInPage extends BusinessRuleViolation
{
    public static function of(string $value): self
    {
        return self::because(sprintf('"%s" is not the LinkedIn page of a company', $value), 'linkedInPage');
    }
}
