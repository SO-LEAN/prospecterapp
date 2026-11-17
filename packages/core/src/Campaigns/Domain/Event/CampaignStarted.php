<?php

declare(strict_types=1);

namespace Prospecter\Core\Campaigns\Domain\Event;

use Prospecter\Core\SharedKernel\Domain\AccountId;
use Prospecter\Core\SharedKernel\Domain\CampaignId;
use Prospecter\Core\SharedKernel\Domain\Event\DomainEvent;
use Prospecter\Core\SharedKernel\Domain\MemberId;

/**
 * A member started a frozen campaign: the team leads it as it was frozen.
 */
final readonly class CampaignStarted extends DomainEvent
{
    public function __construct(AccountId $accountId, MemberId $by, public CampaignId $campaign, \DateTimeImmutable $occurredAt)
    {
        parent::__construct($accountId, $by, $occurredAt);
    }

    public function subject(): CampaignId
    {
        return $this->campaign;
    }
}
