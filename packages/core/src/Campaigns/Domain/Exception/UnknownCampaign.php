<?php

declare(strict_types=1);

namespace Prospecter\Core\Campaigns\Domain\Exception;

use Prospecter\Core\Campaigns\Domain\Model\CampaignId;
use Prospecter\Core\SharedKernel\Domain\Exception\NotFound;

/**
 * No campaign of the account has this identity: discarded, of another account, or never prepared.
 */
final class UnknownCampaign extends NotFound
{
    public static function identifiedBy(CampaignId $id): self
    {
        return new self(sprintf('Campaign %s not found', $id));
    }
}
