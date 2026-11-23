<?php

declare(strict_types=1);

namespace Prospecter\Core\Prospecting\Application\NoteCampaignExchange;

use Prospecter\Core\Prospecting\Domain\Exception\UnknownCampaign;
use Prospecter\Core\Prospecting\Domain\Model\CampaignId;
use Prospecter\Core\Prospecting\Domain\Model\ContactId;
use Prospecter\Core\Prospecting\Domain\Model\Exchange;
use Prospecter\Core\Prospecting\Domain\Model\ExchangeId;
use Prospecter\Core\Prospecting\Domain\Model\ExchangeStatus;
use Prospecter\Core\Prospecting\Domain\Model\MessageReference;
use Prospecter\Core\Prospecting\Domain\Model\Noting;
use Prospecter\Core\Prospecting\Domain\Model\RecipientCorrespondent;
use Prospecter\Core\Prospecting\Domain\Model\RecipientInCampaign;
use Prospecter\Core\Prospecting\Domain\Port\CampaignRecipients;
use Prospecter\Core\Prospecting\Domain\Port\Prospectors;
use Prospecter\Core\Prospecting\Domain\Repository\ExchangeRepository;
use Prospecter\Core\Prospecting\Domain\Service\ContactOrganizations;
use Prospecter\Core\Prospecting\Domain\Service\NotedOnce;
use Prospecter\Core\SharedKernel\Application\CommandHandler;
use Prospecter\Core\SharedKernel\Application\Port\Events;
use Prospecter\Core\SharedKernel\Application\PublishesEvents;
use Prospecter\Core\SharedKernel\Domain\Gesture;
use Prospecter\Core\SharedKernel\Domain\MemberId;
use Prospecter\Core\SharedKernel\Domain\Origin;

/**
 * Campaigns processes the recipient once it reads the exchange's facts, which name the campaign (ADR 0145).
 * An exchange is logged once, as an AI logs what it sent. So the handler recognizes an exchange logged before, and it
 * does so before it asks about the recipient. When a mailbox's import logged that exchange outside any campaign, it
 * becomes the recipient's exchange. Otherwise, logging it again is refused as « already noted » (ADR 0166, 0195).
 */
final readonly class NoteCampaignExchangeHandler implements CommandHandler
{
    use PublishesEvents;

    public function __construct(
        private ExchangeRepository $exchanges,
        private Prospectors $prospectors,
        private CampaignRecipients $campaignRecipients,
        private ContactOrganizations $contactOrganizations,
        private NotedOnce $notedOnce,
        private Events $events,
    ) {
    }

    public function __invoke(NoteCampaignExchange $command): void
    {
        $prospector = $this->prospectors->prospectorFrom(new MemberId($command->prospectorId));
        $campaign = new CampaignId($command->campaignId);
        $contact = new ContactId($command->personId);
        $recipient = $this->campaignRecipients->recipientIn($campaign, $contact, $prospector->accountId) ?? throw UnknownCampaign::identifiedBy($campaign);
        $facts = $recipient->exchangeFacts($command->occurredAt, $command->text);
        $reference = MessageReference::of($facts->channel, $command->messageId, $command->linkedInConversation);

        $notedFirst = $command->noteOnce ? $this->notedOnce->keptUnder($prospector->accountId, $recipient->fingerprintOfSending($command->occurredAt, $command->text, $reference)) : null;

        if (null !== $notedFirst) {
            $this->joinTheCampaign($this->exchanges->get($notedFirst), $recipient, $prospector->id, $command->executedAt);

            return;
        }

        $exchange = Exchange::noted(
            new Gesture($prospector->accountId, $prospector->id, $command->executedAt, Origin::byAnAI($command->byAnAI)),
            new ExchangeId($command->id),
            new RecipientCorrespondent($recipient, $this->contactOrganizations->of($contact, $prospector->accountId)),
            Noting::of($facts, ExchangeStatus::outcome($command->missed), $reference),
        );

        if (!$command->noteOnce) {
            $this->notedOnce->fingerprintUnlessNotedBefore($exchange);
        }

        $this->exchanges->save($exchange);
        $this->publishWhatHappenedTo($exchange);
    }

    private function joinTheCampaign(Exchange $notedFirst, RecipientInCampaign $recipient, MemberId $by, \DateTimeImmutable $at): void
    {
        $notedFirst->joinCampaign($recipient, $by, $at);
        $this->exchanges->save($notedFirst);
        $this->publishWhatHappenedTo($notedFirst);
    }
}
