<?php

declare(strict_types=1);

namespace Prospecter\Core\Prospecting\Application\NoteOrganizationExchange;

use Prospecter\Core\Prospecting\Domain\Channel;
use Prospecter\Core\Prospecting\Domain\ContactOrganizations;
use Prospecter\Core\Prospecting\Domain\Direction;
use Prospecter\Core\Prospecting\Domain\Exchange;
use Prospecter\Core\Prospecting\Domain\ExchangeFacts;
use Prospecter\Core\Prospecting\Domain\ExchangeId;
use Prospecter\Core\Prospecting\Domain\ExchangeRepository;
use Prospecter\Core\Prospecting\Domain\Organization;
use Prospecter\Core\Prospecting\Domain\OrganizationRepository;
use Prospecter\Core\SharedKernel\Application\CommandHandler;
use Prospecter\Core\SharedKernel\Application\Port\Events;
use Prospecter\Core\SharedKernel\Application\PublishesEvents;
use Prospecter\Core\SharedKernel\Domain\MemberId;
use Prospecter\Core\SharedKernel\Domain\OrganizationId;
use Prospecter\Core\SharedKernel\Domain\PersonId;

final readonly class NoteOrganizationExchangeHandler implements CommandHandler
{
    use PublishesEvents;

    public function __construct(
        private ExchangeRepository $exchanges,
        private OrganizationRepository $organizations,
        private ContactOrganizations $contactOrganizations,
        private Events $events,
    ) {
    }

    public function __invoke(NoteOrganizationExchange $command): void
    {
        $author = new MemberId($command->prospectorId);
        $organization = $this->organizations->get(new OrganizationId($command->organizationId));
        $exchange = null === $command->personId
            ? Exchange::notedWithTheOrganization($organization, $author, new ExchangeId($command->id), self::factsOf($command), $command->executedAt)
            : $this->notedWithAContact($organization, $author, new PersonId($command->personId), $command);
        $this->exchanges->save($exchange);
        $this->publishWhatHappenedTo($exchange);
    }

    /**
     * Kept, like any other exchange with a contact, for every organization the contact is part of.
     */
    private function notedWithAContact(Organization $organization, MemberId $author, PersonId $contact, NoteOrganizationExchange $command): Exchange
    {
        $organization->ensureHasContact($contact);

        return Exchange::notedWithAContact($organization->accountId(), $author, new ExchangeId($command->id), $contact, $this->contactOrganizations->of($contact, $organization->accountId()), self::factsOf($command), $command->executedAt);
    }

    private static function factsOf(NoteOrganizationExchange $command): ExchangeFacts
    {
        return new ExchangeFacts(Channel::from($command->channel), Direction::from($command->direction), $command->occurredAt, $command->text, $command->missed);
    }
}
