<?php

declare(strict_types=1);

namespace App\Exchanges;

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
use Prospecter\Core\PersonalData\Application\Person\ConsultPersons\ConsultPersons;
use Prospecter\Core\PersonalData\Application\Person\ConsultPersons\KeptPersons;
use Prospecter\Core\Prospecting\Application\Exchange\ConsultExchange\ConsultExchange;
use Prospecter\Core\Prospecting\Application\Exchange\ConsultExchange\KeptExchange;
use Prospecter\Core\SharedKernel\Application\Handler\QueryHandler;
use Symfony\Component\Clock\ClockInterface;

/**
 * An exchange as the API shows it. Prospecting's query reads it with its participants: its contact and its authors as
 * PersonalData lets the member read them, or the organization itself when the exchange has no contact. The reader
 * gives it to a presenter it builds for the call (ADR 0085, 0217). A command returns nothing, so a controller calls the
 * reader after it to answer with the fresh resource.
 * After a move, the controller passes the moment the exchange was moved from, so that the resource offers to move it
 * back there.
 */
final readonly class ExchangeReader
{
    public function __construct(
        private QueryHandler $consultExchange,
        private ConsultMembersService $consultMembersService,
        private QueryHandler $consultPersons,
        private Links $links,
        private IntentLinks $intents,
        private ClockInterface $clock,
        private ExchangeRevisions $revisions,
    ) {
    }

    public function read(string $prospector, string $id, ?\DateTimeImmutable $movedFrom = null): ExchangeDetailResource
    {
        $presenter = new ExchangeDetailPresenter($this->links, $this->intents, $this->revisions);
        $this->revisions->watch($id);
        $this->consultExchange->__invoke(new ConsultExchange(prospectorId: $prospector, id: $id, asOf: $this->clock->now(), movedFrom: $movedFrom), $presenter);

        return $presenter->resource;
    }

    /**
     * Presents an exchange already read with its participants.
     */
    public function resourceOf(KeptExchange $exchange): ExchangeDetailResource
    {
        $presenter = new ExchangeDetailPresenter($this->links, $this->intents, $this->revisions);
        $presenter->present($exchange->exchange, $exchange->participants);

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
