<?php

declare(strict_types=1);

namespace App\Exchanges;

use App\Contacts\Resource\InterlocutorResource;
use App\Exchanges\Resource\AuthorResource;
use App\Exchanges\Resource\ExchangeDetailResource;
use App\PersonalData\Resource\PersonNameResource;
use App\Prospecting\Persistence\ExchangeRevisions;
use App\Security\IntentLinks;
use App\Shared\Links;
use Prospecter\Core\Accounts\Contract\Service\ConsultMembers\ConsultMembers;
use Prospecter\Core\Accounts\Contract\Service\ConsultMembers\ConsultMembersService;
use Prospecter\Core\Accounts\Contract\Service\ConsultMembers\KeptMembers;
use Prospecter\Core\Accounts\Contract\Service\ConsultMembers\MemberResponse;
use Prospecter\Core\PersonalData\Application\ConsultPersons\ConsultPersons;
use Prospecter\Core\PersonalData\Application\ConsultPersons\KeptPersons;
use Prospecter\Core\PersonalData\Application\PersonCard;
use Prospecter\Core\Prospecting\Application\ConsultExchange\ConsultExchange;
use Prospecter\Core\Prospecting\Application\ConsultExchange\ExchangeDetail;
use Prospecter\Core\Prospecting\Application\ConsultExchange\KeptExchange;
use Prospecter\Core\Prospecting\Application\ConsultOrganization\ConsultOrganization;
use Prospecter\Core\Prospecting\Application\ConsultOrganization\KeptOrganization;
use Prospecter\Core\Prospecting\Application\OrganizationSheet;
use Prospecter\Core\SharedKernel\Application\QueryHandler;
use Symfony\Component\Clock\ClockInterface;

/**
 * An exchange as the API shows it. The reader reads the exchange in Prospecting, with the organization itself when
 * the exchange has no contact. PersonalData names its contact and the members who logged, corrected and redacted it.
 * Accounts gives those members' person ids. The reader then gives the exchange to a presenter it builds for the call
 * (ADR 0085). A command returns nothing, so a controller calls the reader after it to answer with the fresh resource.
 * After a move, the controller passes the moment the exchange was moved from, so that the resource offers to move it
 * back there.
 */
final readonly class ExchangeReader
{
    public function __construct(
        private QueryHandler $consultExchange,
        private ConsultMembersService $consultMembersService,
        private QueryHandler $consultPersons,
        private QueryHandler $consultOrganization,
        private Links $links,
        private IntentLinks $intents,
        private ClockInterface $clock,
        private ExchangeRevisions $revisions,
    ) {
    }

    public function read(string $prospector, string $id, ?\DateTimeImmutable $movedFrom = null): ExchangeDetailResource
    {
        $kept = new KeptExchange();
        $this->revisions->watch($id);
        $this->consultExchange->__invoke(new ConsultExchange(prospectorId: $prospector, id: $id, asOf: $this->clock->now(), movedFrom: $movedFrom), $kept);

        return $this->resourceOf($prospector, $kept->exchange);
    }

    /**
     * Presents an exchange already read, with its interlocutor and its authors named.
     */
    public function resourceOf(string $prospector, ExchangeDetail $exchange): ExchangeDetailResource
    {
        $presenter = new ExchangeDetailPresenter($this->participantsOf($prospector, $exchange), $this->links, $this->intents, $this->revisions);
        $presenter->present($exchange);

        return $presenter->resource;
    }

    /**
     * A member of the account who acted on an exchange, named the same way as the exchange's authors.
     */
    public function memberNamed(string $prospector, string $member): AuthorResource
    {
        $personOfMember = $this->personsOfMembers([$member]);
        $persons = new KeptPersons();
        $this->consultPersons->__invoke(new ConsultPersons($prospector, array_values($personOfMember)), $persons);
        $person = $persons->persons[$personOfMember[$member] ?? ''] ?? null;

        return new AuthorResource(new PersonNameResource($person?->firstName, $person?->lastName), $person?->email);
    }

    private function participantsOf(string $prospector, ExchangeDetail $exchange): ExchangeParticipants
    {
        $personOfMember = $this->personsOfMembers(array_filter([$exchange->createdBy, $exchange->correctedBy, $exchange->madeConformBy]));
        $persons = new KeptPersons();
        $this->consultPersons->__invoke(new ConsultPersons($prospector, array_values(array_unique(array_filter([$exchange->personId, ...array_values($personOfMember)])))), $persons);

        if (null === $exchange->personId) {
            $organization = $this->organizationOf($exchange->organizationIds[0]);

            return new ExchangeParticipants(InterlocutorResource::ofTheOrganization($organization), $persons->persons, $personOfMember, $organization->phoneNumber);
        }

        $contact = $persons->persons[$exchange->personId] ?? null;

        return new ExchangeParticipants(InterlocutorResource::ofPerson($exchange->personId, $contact, $exchange->contactPresence), $persons->persons, $personOfMember, self::numberToCall($contact));
    }

    /**
     * Only a number read in clear can be called. A masked number calls nobody.
     */
    private static function numberToCall(?PersonCard $contact): ?string
    {
        return null === $contact || $contact->masked ? null : $contact->phoneNumber;
    }

    /**
     * An exchange with no contact is kept for the organization itself only.
     */
    private function organizationOf(string $id): OrganizationSheet
    {
        $kept = new KeptOrganization();
        $this->consultOrganization->__invoke(new ConsultOrganization($id), $kept);

        return $kept->organization;
    }

    /**
     * Asks Accounts once for all the members (ADR 0089).
     *
     * @param array<string> $members
     *
     * @return array<string, string> the person id of each member found, by member id
     */
    private function personsOfMembers(array $members): array
    {
        $kept = new KeptMembers();
        ($this->consultMembersService)(new ConsultMembers(array_values(array_unique($members))), $kept);

        return array_map(static fn (MemberResponse $member): string => $member->personId, $kept->members);
    }
}
