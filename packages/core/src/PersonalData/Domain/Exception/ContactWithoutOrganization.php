<?php

declare(strict_types=1);

namespace Prospecter\Core\PersonalData\Domain\Exception;

use Prospecter\Core\SharedKernel\Domain\Exception\BusinessRuleViolation;

/**
 * A contact is collected for at least one organization (ADR 0042).
 */
final class ContactWithoutOrganization extends BusinessRuleViolation
{
}
