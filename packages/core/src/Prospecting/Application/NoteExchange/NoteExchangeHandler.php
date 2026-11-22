<?php

declare(strict_types=1);

namespace Prospecter\Core\Prospecting\Application\NoteExchange;

use Prospecter\Core\Prospecting\Domain\Model\Channel;
use Prospecter\Core\Prospecting\Domain\Model\Correspondent;
use Prospecter\Core\Prospecting\Domain\Model\Direction;
use Prospecter\Core\Prospecting\Domain\Model\Exchange;
use Prospecter\Core\Prospecting\Domain\Model\ExchangeFacts;
use Prospecter\Core\Prospecting\Domain\Model\ExchangeId;
use Prospecter\Core\Prospecting\Domain\Model\ExchangeStatus;
use Prospecter\Core\Prospecting\Domain\Model\MessageReference;
use Prospecter\Core\Prospecting\Domain\Model\Noting;
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
use Prospecter\Core\SharedKernel\Domain\PersonId;

final readonly class NoteExchangeHandler implements CommandHandler
{
    use PublishesEvents;

    public function __construct(
        private ExchangeRepository $exchanges,
        private Prospectors $prospectors,
        private ContactOrganizations $contactOrganizations,
        private NotedOnce $notedOnce,
        private Events $events,
    ) {
    }

    public function __invoke(NoteExchange $command): void
    {
        $prospector = $this->prospectors->prospectorFrom(new MemberId($command->prospectorId));
        $contact = new PersonId($command->personId);
        $facts = new ExchangeFacts(Channel::from($command->channel), Direction::from($command->direction), $command->occurredAt, $command->text);
        $exchange = Exchange::noted(
            new Gesture($prospector->accountId, $prospector->id, $command->executedAt, Origin::byAnAI($command->byAnAI)),
            new ExchangeId($command->id),
            Correspondent::contact($contact, $this->contactOrganizations->keepersOf($contact, $prospector)),
            Noting::of($facts, ExchangeStatus::outcome($command->missed), MessageReference::of($facts->channel, $command->messageId, $command->linkedInConversation)),
        );
        if ($command->noteOnce) {
            $this->notedOnce->ensureFirst($exchange);
        } else {
            $this->notedOnce->fingerprintUnlessNotedBefore($exchange);
        }
        $this->exchanges->save($exchange);
        $this->publishWhatHappenedTo($exchange);
    }
}
