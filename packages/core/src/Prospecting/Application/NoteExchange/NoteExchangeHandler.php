<?php

declare(strict_types=1);

namespace Prospecter\Core\Prospecting\Application\NoteExchange;

use Prospecter\Core\Prospecting\Domain\Model\Channel;
use Prospecter\Core\Prospecting\Domain\Model\Direction;
use Prospecter\Core\Prospecting\Domain\Model\Exchange;
use Prospecter\Core\Prospecting\Domain\Model\ExchangeFacts;
use Prospecter\Core\Prospecting\Domain\Model\ExchangeId;
use Prospecter\Core\Prospecting\Domain\Port\Prospectors;
use Prospecter\Core\Prospecting\Domain\Repository\ExchangeRepository;
use Prospecter\Core\Prospecting\Domain\Service\ContactOrganizations;
use Prospecter\Core\SharedKernel\Application\CommandHandler;
use Prospecter\Core\SharedKernel\Application\Port\Events;
use Prospecter\Core\SharedKernel\Application\PublishesEvents;
use Prospecter\Core\SharedKernel\Domain\MemberId;
use Prospecter\Core\SharedKernel\Domain\PersonId;

final readonly class NoteExchangeHandler implements CommandHandler
{
    use PublishesEvents;

    public function __construct(
        private ExchangeRepository $exchanges,
        private Prospectors $prospectors,
        private ContactOrganizations $contactOrganizations,
        private Events $events,
    ) {
    }

    public function __invoke(NoteExchange $command): void
    {
        $prospector = $this->prospectors->prospectorFrom(new MemberId($command->prospectorId));
        $contact = new PersonId($command->personId);
        $exchange = Exchange::notedWithAContact(
            $prospector->accountId,
            $prospector->id,
            new ExchangeId($command->id),
            $contact,
            $this->contactOrganizations->of($contact, $prospector->accountId),
            new ExchangeFacts(Channel::from($command->channel), Direction::from($command->direction), $command->occurredAt, $command->text, $command->missed),
            $command->executedAt,
        );
        $this->exchanges->save($exchange);
        $this->publishWhatHappenedTo($exchange);
    }
}
