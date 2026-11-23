<?php

declare(strict_types=1);

namespace Prospecter\Core\Campaigns\Application\ReopenRecipientOfErasedExchange;

use Prospecter\Core\Campaigns\Domain\Model\CampaignId;
use Prospecter\Core\Campaigns\Domain\Repository\CampaignRepository;
use Prospecter\Core\Prospecting\Contract\Event\ExchangeFactsChanged;
use Prospecter\Core\SharedKernel\Application\EventHandler;
use Prospecter\Core\SharedKernel\Domain\AccountId;

/**
 * Puts a recipient back to handle when a member erases, in Prospecting, the exchange that processed them. An ended
 * campaign runs again (ADR 0152). Nothing changes when the event is delivered again, or when it is about an exchange
 * outside any campaign, an erased campaign or an exchange that processed nobody. An exchange that went with its
 * organization leaves its recipient processed, without a link to the exchange (ADR 0155).
 */
final readonly class ReopenRecipientOfErasedExchangeHandler implements EventHandler
{
    public function __construct(private CampaignRepository $campaigns)
    {
    }

    public function __invoke(ExchangeFactsChanged $event): void
    {
        if ($event->present || null === $event->campaignId) {
            return;
        }

        $campaign = $this->campaigns->find(new AccountId($event->accountId), new CampaignId($event->campaignId));

        if (null === $campaign) {
            return;
        }

        if ($event->erasedWithItsOrganization) {
            $campaign->forgetExchangeGoneWithItsOrganization($event->exchangeId);
        } else {
            $campaign->reopenAfterErasureOf($event->exchangeId, $event->occurredAt);
        }

        $this->campaigns->save($campaign);
    }
}
