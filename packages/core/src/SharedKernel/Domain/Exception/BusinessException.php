<?php

declare(strict_types=1);

namespace Prospecter\Core\SharedKernel\Domain\Exception;

/**
 * A business refusal: the domain throws it when a rule says no. The application translates it for its clients.
 * Anything else that is thrown is an infrastructure failure.
 */
abstract class BusinessException extends \DomainException
{
}
