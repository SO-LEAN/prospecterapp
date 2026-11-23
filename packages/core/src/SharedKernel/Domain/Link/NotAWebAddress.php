<?php

declare(strict_types=1);

namespace Prospecter\Core\SharedKernel\Domain\Link;

use Prospecter\Core\SharedKernel\Domain\Exception\BusinessRuleViolation;

/**
 * The text given as a link is not a web page's address.
 */
final class NotAWebAddress extends BusinessRuleViolation
{
    public static function of(string $value): self
    {
        return self::because(sprintf('"%s" is not the address of a web page', $value), 'links', context: ['link' => $value]);
    }
}
