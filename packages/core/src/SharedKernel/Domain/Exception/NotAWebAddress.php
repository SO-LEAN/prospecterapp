<?php

declare(strict_types=1);

namespace Prospecter\Core\SharedKernel\Domain\Exception;

/**
 * What was given as a link is no address of a page on the web.
 */
final class NotAWebAddress extends BusinessRuleViolation
{
    public static function of(string $value): self
    {
        return self::because(sprintf('"%s" is not the address of a web page', $value), 'links', context: ['link' => $value]);
    }
}
