<?php

declare(strict_types=1);

namespace Prospecter\Core\Campaigns\Application\ForgetRecipientOfForgottenContact;

use Prospecter\Core\Campaigns\Domain\Model\ContactId;
use Prospecter\Core\Campaigns\Domain\Repository\CampaignRepository;
use Prospecter\Core\PersonalData\Contract\Event\ContactForgotten;
use Prospecter\Core\SharedKernel\Application\EventHandler;
use Prospecter\Core\SharedKernel\Domain\Identity\AccountId;

/**
 * A contact that PersonalData forgot leaves the frozen campaigns that keep it, even as their last recipient. When the
 * event is delivered again, no campaign keeps the contact any more. A started campaign keeps the contact as erased, and
 * ends once nobody is left to handle.
 */
final readonly class ForgetRecipientOfForgottenContactHandler implements EventHandler
{
    public function __construct(private CampaignRepository $campaigns)
    {
    }

    public function __invoke(ContactForgotten $event): void
    {
        $contact = new ContactId($event->personId);

        foreach ($this->campaigns->withRecipient(new AccountId($event->accountId), $contact) as $campaign) {
            $campaign->forget($contact, $event->occurredAt);
            $this->campaigns->save($campaign);
        }
    }
}
