<?php

declare(strict_types=1);

namespace Prospecter\Core\Prospecting\Application\PlanOrganizationExchange;

use Prospecter\Core\Prospecting\Domain\Model\Channel;
use Prospecter\Core\Prospecting\Domain\Model\Duration;
use Prospecter\Core\Prospecting\Domain\Model\Exchange;
use Prospecter\Core\Prospecting\Domain\Model\ExchangeFacts;
use Prospecter\Core\Prospecting\Domain\Model\ExchangeId;
use Prospecter\Core\Prospecting\Domain\Model\Organization;
use Prospecter\Core\Prospecting\Domain\Model\VideoCallLink;
use Prospecter\Core\Prospecting\Domain\Repository\ExchangeRepository;
use Prospecter\Core\Prospecting\Domain\Repository\OrganizationRepository;
use Prospecter\Core\Prospecting\Domain\Service\ContactOrganizations;
use Prospecter\Core\SharedKernel\Application\CommandHandler;
use Prospecter\Core\SharedKernel\Application\Port\Events;
use Prospecter\Core\SharedKernel\Application\PublishesEvents;
use Prospecter\Core\SharedKernel\Domain\MemberId;
use Prospecter\Core\SharedKernel\Domain\OrganizationId;
use Prospecter\Core\SharedKernel\Domain\PersonId;

final readonly class PlanOrganizationExchangeHandler implements CommandHandler
{
    use PublishesEvents;

    public function __construct(
        private ExchangeRepository $exchanges,
        private OrganizationRepository $organizations,
        private ContactOrganizations $contactOrganizations,
        private Events $events,
    ) {
    }

    public function __invoke(PlanOrganizationExchange $command): void
    {
        $holder = new MemberId($command->prospectorId);
        $organization = $this->organizations->get(new OrganizationId($command->organizationId));
        $exchange = null === $command->personId
            ? Exchange::plannedWithTheOrganization($organization, $holder, new ExchangeId($command->id), self::factsOf($command), $command->executedAt)
            : $this->plannedWithAContact($organization, $holder, new PersonId($command->personId), $command);
        $this->exchanges->save($exchange);
        $this->publishWhatHappenedTo($exchange);
    }

    /**
     * Kept, like any other exchange with a contact, for every organization the contact is part of.
     */
    private function plannedWithAContact(Organization $organization, MemberId $holder, PersonId $contact, PlanOrganizationExchange $command): Exchange
    {
        $organization->ensureHasContact($contact);

        return Exchange::plannedWithAContact($organization->accountId(), $holder, new ExchangeId($command->id), $contact, $this->contactOrganizations->of($contact, $organization->accountId()), self::factsOf($command), $command->executedAt);
    }

    private static function factsOf(PlanOrganizationExchange $command): ExchangeFacts
    {
        return ExchangeFacts::planned(Channel::from($command->channel), $command->plannedAt, Duration::inMinutes($command->durationInMinutes), $command->agenda, $command->place, VideoCallLink::orNone($command->link));
    }
}
