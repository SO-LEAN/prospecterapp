<?php

declare(strict_types=1);

namespace Prospecter\Core\Prospecting\Domain\Exception;

use Prospecter\Core\SharedKernel\Domain\CampaignId;
use Prospecter\Core\SharedKernel\Domain\Exception\NotFound;

/**
 * Campaigns knows no campaign of the account with this identity.
 */
final class UnknownCampaign extends NotFound
{
    public static function identifiedBy(CampaignId $id): self
    {
        return new self(sprintf('Campaign %s not found', $id));
    }
}
