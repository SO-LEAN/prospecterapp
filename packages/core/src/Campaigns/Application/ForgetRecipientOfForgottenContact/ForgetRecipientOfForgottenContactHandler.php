<?php

declare(strict_types=1);

namespace Prospecter\Core\Campaigns\Application\ForgetRecipientOfForgottenContact;

use Prospecter\Core\Campaigns\Domain\Repository\CampaignRepository;
use Prospecter\Core\PersonalData\Contract\Event\ContactForgotten;
use Prospecter\Core\SharedKernel\Application\EventHandler;
use Prospecter\Core\SharedKernel\Domain\AccountId;
use Prospecter\Core\SharedKernel\Domain\PersonId;

/**
 * A contact PersonalData forgot leaves the frozen campaigns keeping it, even as their last recipient; delivered
 * again, no campaign keeps it any more. A started campaign keeps it erased, and ends once nobody is left to handle.
 */
final readonly class ForgetRecipientOfForgottenContactHandler implements EventHandler
{
    public function __construct(private CampaignRepository $campaigns)
    {
    }

    public function __invoke(ContactForgotten $event): void
    {
        $contact = new PersonId($event->personId);

        foreach ($this->campaigns->withRecipient(new AccountId($event->accountId), $contact) as $campaign) {
            $campaign->forget($contact, $event->occurredAt);
            $this->campaigns->save($campaign);
        }
    }
}
