<?php

declare(strict_types=1);

namespace Prospecter\Core\Prospecting\Domain\Exception;

use Prospecter\Core\SharedKernel\Domain\Exception\BusinessRuleViolation;

/**
 * A Message-ID identifies only an e-mail. A LinkedIn conversation identifies only a LinkedIn message.
 */
final class ReferenceNotOfTheChannel extends BusinessRuleViolation
{
}
