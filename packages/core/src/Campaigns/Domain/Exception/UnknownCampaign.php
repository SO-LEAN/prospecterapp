<?php

declare(strict_types=1);

namespace Prospecter\Core\Campaigns\Domain\Exception;

use Prospecter\Core\Campaigns\Domain\Model\CampaignId;
use Prospecter\Core\SharedKernel\Domain\Exception\NotFound;

/**
 * No campaign of the account has this id. The campaign was discarded, belongs to another account, or was never
 * prepared.
 */
final class UnknownCampaign extends NotFound
{
    public static function identifiedBy(CampaignId $id): self
    {
        return new self(sprintf('Campaign %s not found', $id));
    }
}
