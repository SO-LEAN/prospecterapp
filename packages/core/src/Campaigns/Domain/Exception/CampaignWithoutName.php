<?php

declare(strict_types=1);

namespace Prospecter\Core\Campaigns\Domain\Exception;

use Prospecter\Core\SharedKernel\Domain\Exception\BusinessRuleViolation;

/**
 * The team finds its campaigns by their names: one without is refused.
 */
final class CampaignWithoutName extends BusinessRuleViolation
{
}
