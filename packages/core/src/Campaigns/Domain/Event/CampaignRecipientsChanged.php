<?php

declare(strict_types=1);

namespace Prospecter\Core\Campaigns\Domain\Event;

use Prospecter\Core\Campaigns\Domain\Model\CampaignId;
use Prospecter\Core\SharedKernel\Domain\AccountId;
use Prospecter\Core\SharedKernel\Domain\Event\DomainEvent;
use Prospecter\Core\SharedKernel\Domain\MemberId;

/** A member added recipients to a frozen campaign, or removed one. */
final readonly class CampaignRecipientsChanged extends DomainEvent
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
