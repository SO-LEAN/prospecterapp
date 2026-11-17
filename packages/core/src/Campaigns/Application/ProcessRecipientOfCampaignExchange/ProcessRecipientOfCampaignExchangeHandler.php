<?php

declare(strict_types=1);

namespace Prospecter\Core\Campaigns\Application\ProcessRecipientOfCampaignExchange;

use Prospecter\Core\Campaigns\Domain\Repository\CampaignRepository;
use Prospecter\Core\Prospecting\Contract\Event\ExchangeFactsChanged;
use Prospecter\Core\SharedKernel\Application\EventHandler;
use Prospecter\Core\SharedKernel\Domain\AccountId;
use Prospecter\Core\SharedKernel\Domain\CampaignId;
use Prospecter\Core\SharedKernel\Domain\PersonId;

/**
 * The exchange Prospecting noted for a recipient in a campaign processes them, done or missed (ADR 0145): the
 * campaign ends once nobody is left to handle. Delivered again, or for an exchange of no campaign, a campaign erased
 * or a recipient handled already, nothing changes.
 */
final readonly class ProcessRecipientOfCampaignExchangeHandler implements EventHandler
{
    private const array OUTCOMES = ['done' => false, 'missed' => true];

    public function __construct(private CampaignRepository $campaigns)
    {
    }

    public function __invoke(ExchangeFactsChanged $event): void
    {
        if (null === $event->campaignId || null === $event->contactId || !$event->present || !array_key_exists((string) $event->status, self::OUTCOMES)) {
            return;
        }

        $campaign = $this->campaigns->find(new AccountId($event->accountId), new CampaignId($event->campaignId));

        if (null !== $campaign) {
            $campaign->process(new PersonId($event->contactId), self::OUTCOMES[(string) $event->status], $event->occurredAt);
            $this->campaigns->save($campaign);
        }
    }
}
