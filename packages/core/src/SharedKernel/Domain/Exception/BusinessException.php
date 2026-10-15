<?php

declare(strict_types=1);

namespace Prospecter\Core\SharedKernel\Domain\Exception;

/**
 * A refusal of the business: what the domain throws when a rule says no. The application translates it for
 * its clients; anything else thrown is a failure of the infrastructure.
 */
abstract class BusinessException extends \DomainException
{
}
