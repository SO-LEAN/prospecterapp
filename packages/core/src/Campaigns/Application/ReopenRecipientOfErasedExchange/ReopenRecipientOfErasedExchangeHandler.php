<?php

declare(strict_types=1);

namespace Prospecter\Core\Campaigns\Application\ReopenRecipientOfErasedExchange;

use Prospecter\Core\Campaigns\Domain\Repository\CampaignRepository;
use Prospecter\Core\Prospecting\Contract\Event\ExchangeFactsChanged;
use Prospecter\Core\SharedKernel\Application\EventHandler;
use Prospecter\Core\SharedKernel\Domain\AccountId;
use Prospecter\Core\SharedKernel\Domain\CampaignId;

/**
 * The exchange that processed a recipient erased in Prospecting by a member, they are to handle again, an ended
 * campaign led again (ADR 0152). Delivered again, or for an exchange of no campaign, a campaign erased, an exchange
 * that processed nobody or one gone with its organization (ADR 0155), nothing changes.
 */
final readonly class ReopenRecipientOfErasedExchangeHandler implements EventHandler
{
    public function __construct(private CampaignRepository $campaigns)
    {
    }

    public function __invoke(ExchangeFactsChanged $event): void
    {
        if ($event->present || $event->erasedWithItsOrganization || null === $event->campaignId) {
            return;
        }

        $campaign = $this->campaigns->find(new AccountId($event->accountId), new CampaignId($event->campaignId));

        if (null !== $campaign) {
            $campaign->reopenAfterErasureOf($event->exchangeId, $event->occurredAt);
            $this->campaigns->save($campaign);
        }
    }
}
