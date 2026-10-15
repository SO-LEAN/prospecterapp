<?php

declare(strict_types=1);

namespace Prospecter\Core\Domain\Shared;

final class EmailAlreadyUsed extends BusinessRuleViolation
{
    public function __construct()
    {
        parent::__construct('Email already used', 'email');
    }
}
