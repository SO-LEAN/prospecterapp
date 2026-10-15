<?php

declare(strict_types=1);

namespace Prospecter\Core\SharedKernel\Domain;

/**
 * An intent the one asking may see but not carry out: their link is missing, for want of a right.
 */
final class NotAllowed extends BusinessRuleViolation
{
}
