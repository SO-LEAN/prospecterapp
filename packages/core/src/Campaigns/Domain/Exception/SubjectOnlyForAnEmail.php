<?php

declare(strict_types=1);

namespace Prospecter\Core\Campaigns\Domain\Exception;

use Prospecter\Core\SharedKernel\Domain\Exception\BusinessRuleViolation;

/**
 * A call, a SMS or a LinkedIn message has no subject: one given is refused rather than lost.
 */
final class SubjectOnlyForAnEmail extends BusinessRuleViolation
{
}
