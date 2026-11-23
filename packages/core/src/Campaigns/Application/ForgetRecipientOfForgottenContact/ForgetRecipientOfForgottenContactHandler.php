<?php

declare(strict_types=1);

namespace Prospecter\Core\Campaigns\Application\ForgetRecipientOfForgottenContact;

use Prospecter\Core\Campaigns\Domain\Model\ContactId;
use Prospecter\Core\Campaigns\Domain\Repository\CampaignRepository;
use Prospecter\Core\SharedKernel\Application\Handler\CommandHandler;
use Prospecter\Core\SharedKernel\Domain\Identity\AccountId;

/**
 * A contact that PersonalData forgot leaves the frozen campaigns that keep it, even as their last recipient. When the
 * command comes again, no campaign keeps the contact any more. A started campaign keeps the contact as erased, and
 * ends once nobody is left to handle.
 */
final readonly class ForgetRecipientOfForgottenContactHandler implements CommandHandler
{
    public function __construct(private CampaignRepository $campaigns)
    {
    }

    public function __invoke(ForgetRecipientOfForgottenContact $command): void
    {
        $contact = new ContactId($command->contactId);

        foreach ($this->campaigns->withRecipient(new AccountId($command->accountId), $contact) as $campaign) {
            $campaign->forget($contact, $command->executedAt);
            $this->campaigns->save($campaign);
        }
    }
}
