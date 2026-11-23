<?php

declare(strict_types=1);

namespace Prospecter\Core\Campaigns\Application\ProcessRecipientOfCampaignExchange;

use Prospecter\Core\Campaigns\Domain\Model\CampaignId;
use Prospecter\Core\Campaigns\Domain\Model\RecipientOutcome;
use Prospecter\Core\Campaigns\Domain\Repository\CampaignRepository;
use Prospecter\Core\Prospecting\Contract\Event\ExchangeFactsChanged;
use Prospecter\Core\SharedKernel\Application\EventHandler;
use Prospecter\Core\SharedKernel\Domain\AccountId;
use Prospecter\Core\SharedKernel\Domain\PersonId;

/**
 * Marks the recipient as done or missed when Prospecting logs their exchange in a campaign (ADR 0145). The campaign
 * ends once nobody is left to handle. Nothing changes when the event is delivered again, or when it is about an
 * exchange outside any campaign, an erased campaign or a recipient already handled.
 */
final readonly class ProcessRecipientOfCampaignExchangeHandler implements EventHandler
{
    public function __construct(private CampaignRepository $campaigns)
    {
    }

    public function __invoke(ExchangeFactsChanged $event): void
    {
        $outcome = self::outcomeOf($event->status);

        if (null === $event->campaignId || null === $event->contactId || !$event->present || null === $outcome) {
            return;
        }

        $campaign = $this->campaigns->find(new AccountId($event->accountId), new CampaignId($event->campaignId));

        if (null !== $campaign) {
            $campaign->process(new PersonId($event->contactId), $event->exchangeId, $outcome, $event->occurredAt);
            $this->campaigns->save($campaign);
        }
    }

    /** Translates the exchange's status in Prospecting. A planned exchange processes nobody yet. */
    private static function outcomeOf(?string $status): ?RecipientOutcome
    {
        return match ($status) {
            'done' => RecipientOutcome::Done,
            'missed' => RecipientOutcome::Missed,
            default => null,
        };
    }
}
