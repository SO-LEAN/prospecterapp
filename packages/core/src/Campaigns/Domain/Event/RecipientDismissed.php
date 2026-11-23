<?php

declare(strict_types=1);

namespace Prospecter\Core\Campaigns\Domain\Event;

use Prospecter\Core\Campaigns\Domain\Model\CampaignId;
use Prospecter\Core\Campaigns\Domain\Model\ContactId;
use Prospecter\Core\SharedKernel\Domain\Event\DomainEvent;
use Prospecter\Core\SharedKernel\Domain\Identity\AccountId;
use Prospecter\Core\SharedKernel\Domain\Identity\MemberId;

/**
 * A member dismissed a recipient of a started campaign, without an exchange.
 */
final readonly class RecipientDismissed extends DomainEvent
{
    public function __construct(AccountId $accountId, MemberId $by, public CampaignId $campaign, public ContactId $contact, \DateTimeImmutable $occurredAt)
    {
        parent::__construct($accountId, $by, $occurredAt);
    }

    public function subject(): CampaignId
    {
        return $this->campaign;
    }
}
