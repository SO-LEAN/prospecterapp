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
use Prospecter\Core\Prospecting\Domain\Prospector;
use Prospecter\Core\Prospecting\Domain\ProspectorService;
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
        private ProspectorService $prospectorService,
        private ContactOrganizations $contactOrganizations,
        private Events $events,
    ) {
    }

    public function __invoke(NoteOrganizationExchange $command): void
    {
        $prospector = $this->prospectorService->prospectorFrom(new MemberId($command->prospectorId));
        $organization = $this->organizations->get(new OrganizationId($command->organizationId));
        $exchange = null === $command->personId
            ? Exchange::notedWithTheOrganization($organization, $prospector, new ExchangeId($command->id), self::factsOf($command), $command->executedAt)
            : $this->notedWithAContact($organization, $prospector, new PersonId($command->personId), $command);
        $this->exchanges->save($exchange);
        $this->publishWhatHappenedTo($exchange);
    }

    /**
     * Kept, like any other exchange with a contact, for every organization the contact is part of.
     */
    private function notedWithAContact(Organization $organization, Prospector $prospector, PersonId $contact, NoteOrganizationExchange $command): Exchange
    {
        $organization->ensureHasContact($contact, $prospector);

        return Exchange::notedBy($prospector, new ExchangeId($command->id), $contact, $this->contactOrganizations->of($contact, $prospector), self::factsOf($command), $command->executedAt);
    }

    private static function factsOf(NoteOrganizationExchange $command): ExchangeFacts
    {
        return new ExchangeFacts(Channel::from($command->channel), Direction::from($command->direction), $command->occurredAt, $command->text, $command->missed);
    }
}
