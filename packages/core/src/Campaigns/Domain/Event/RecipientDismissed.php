<?php

declare(strict_types=1);

namespace Prospecter\Core\Campaigns\Domain\Event;

use Prospecter\Core\Campaigns\Domain\Model\CampaignId;
use Prospecter\Core\SharedKernel\Domain\AccountId;
use Prospecter\Core\SharedKernel\Domain\Event\DomainEvent;
use Prospecter\Core\SharedKernel\Domain\MemberId;
use Prospecter\Core\SharedKernel\Domain\PersonId;

/**
 * A member dismissed a recipient of a started campaign, without an exchange.
 */
final readonly class RecipientDismissed extends DomainEvent
{
    public function __construct(AccountId $accountId, MemberId $by, public CampaignId $campaign, public PersonId $contact, \DateTimeImmutable $occurredAt)
    {
        parent::__construct($accountId, $by, $occurredAt);
    }

    public function subject(): CampaignId
    {
        return $this->campaign;
    }
}
