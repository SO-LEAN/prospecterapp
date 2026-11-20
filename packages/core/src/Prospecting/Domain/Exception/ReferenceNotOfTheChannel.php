<?php

declare(strict_types=1);

namespace Prospecter\Core\Prospecting\Domain\Exception;

use Prospecter\Core\SharedKernel\Domain\Exception\BusinessRuleViolation;

/**
 * A Message-ID identifies an e-mail only, a conversation a LinkedIn message only.
 */
final class ReferenceNotOfTheChannel extends BusinessRuleViolation
{
}
