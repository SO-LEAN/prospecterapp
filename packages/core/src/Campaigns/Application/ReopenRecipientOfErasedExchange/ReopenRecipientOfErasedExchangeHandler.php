<?php

declare(strict_types=1);

namespace Prospecter\Core\Campaigns\Application\ReopenRecipientOfErasedExchange;

use Prospecter\Core\Campaigns\Domain\Model\CampaignId;
use Prospecter\Core\Campaigns\Domain\Repository\CampaignRepository;
use Prospecter\Core\SharedKernel\Application\Handler\CommandHandler;
use Prospecter\Core\SharedKernel\Domain\Identity\AccountId;

/**
 * Puts a recipient back to handle when a member erases, in Prospecting, the exchange that processed them. An ended
 * campaign runs again (ADR 0152). Nothing changes when the command comes again, or when it is about an erased
 * campaign or an exchange that processed nobody. An exchange that went with its organization leaves its recipient
 * processed, without a link to the exchange (ADR 0155).
 */
final readonly class ReopenRecipientOfErasedExchangeHandler implements CommandHandler
{
    public function __construct(private CampaignRepository $campaigns)
    {
    }

    public function __invoke(ReopenRecipientOfErasedExchange $command): void
    {
        $campaign = $this->campaigns->find(new AccountId($command->accountId), new CampaignId($command->campaignId));

        if (null === $campaign) {
            return;
        }

        if ($command->erasedWithItsOrganization) {
            $campaign->forgetExchangeGoneWithItsOrganization($command->exchangeId);
        } else {
            $campaign->reopenAfterErasureOf($command->exchangeId, $command->executedAt);
        }

        $this->campaigns->save($campaign);
    }
}
