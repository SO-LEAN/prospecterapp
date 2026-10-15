<?php

declare(strict_types=1);

namespace Prospecter\Core\SharedKernel\Domain\Exception;

final class EmailAlreadyUsed extends BusinessRuleViolation
{
    public static function byAnother(?\Throwable $detectedBy = null): self
    {
        return self::because('Email already used', 'email', $detectedBy);
    }
}
