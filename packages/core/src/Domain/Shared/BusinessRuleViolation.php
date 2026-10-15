<?php

declare(strict_types=1);

namespace Prospecter\Core\Domain\Shared;

/**
 * A change the business refuses, on a given field of the request ("*" for the request as a whole).
 */
class BusinessRuleViolation extends \DomainException
{
    public function __construct(string $message, public readonly string $field = '*')
    {
        parent::__construct($message);
    }
}
