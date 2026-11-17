<?php

declare(strict_types=1);

namespace Prospecter\Core\Prospecting\Application\NoteCampaignExchange;

use Prospecter\Core\Prospecting\Domain\Exception\UnknownCampaign;
use Prospecter\Core\Prospecting\Domain\Model\Exchange;
use Prospecter\Core\Prospecting\Domain\Model\ExchangeId;
use Prospecter\Core\Prospecting\Domain\Port\CampaignRecipients;
use Prospecter\Core\Prospecting\Domain\Port\Prospectors;
use Prospecter\Core\Prospecting\Domain\Repository\ExchangeRepository;
use Prospecter\Core\Prospecting\Domain\Service\ContactOrganizations;
use Prospecter\Core\SharedKernel\Application\CommandHandler;
use Prospecter\Core\SharedKernel\Application\Port\Events;
use Prospecter\Core\SharedKernel\Application\PublishesEvents;
use Prospecter\Core\SharedKernel\Domain\CampaignId;
use Prospecter\Core\SharedKernel\Domain\MemberId;
use Prospecter\Core\SharedKernel\Domain\PersonId;

/**
 * Campaigns processes the recipient once it reads the facts of the exchange, which name the campaign (ADR 0145).
 */
final readonly class NoteCampaignExchangeHandler implements CommandHandler
{
    use PublishesEvents;

    public function __construct(
        private ExchangeRepository $exchanges,
        private Prospectors $prospectors,
        private CampaignRecipients $campaignRecipients,
        private ContactOrganizations $contactOrganizations,
        private Events $events,
    ) {
    }

    public function __invoke(NoteCampaignExchange $command): void
    {
        $prospector = $this->prospectors->prospectorFrom(new MemberId($command->prospectorId));
        $campaign = new CampaignId($command->campaignId);
        $contact = new PersonId($command->personId);
        $recipient = $this->campaignRecipients->recipientIn($campaign, $contact, $prospector->accountId) ?? throw UnknownCampaign::identifiedBy($campaign);
        $exchange = Exchange::notedInACampaign(
            $prospector->accountId,
            $prospector->id,
            new ExchangeId($command->id),
            $recipient,
            $this->contactOrganizations->of($contact, $prospector->accountId),
            $command->occurredAt,
            $command->text,
            $command->missed,
            $command->executedAt,
        );
        $this->exchanges->save($exchange);
        $this->publishWhatHappenedTo($exchange);
    }
}
