<?php

declare(strict_types=1);

namespace Prospecter\Core\PersonalData\Application\IdentifyPersons;

use Prospecter\Core\PersonalData\Application\PersonCard;
use Prospecter\Core\PersonalData\Application\Port\DesignatedContactRetriever;
use Prospecter\Core\PersonalData\Application\Port\PersonCardRetriever;
use Prospecter\Core\PersonalData\Domain\Model\OrganizationId;
use Prospecter\Core\PersonalData\Domain\Model\PersonId;
use Prospecter\Core\PersonalData\Domain\Model\PersonKeys;
use Prospecter\Core\PersonalData\Domain\Model\PersonName;
use Prospecter\Core\PersonalData\Domain\Model\PersonResemblanceKey;
use Prospecter\Core\PersonalData\Domain\Model\Reader;
use Prospecter\Core\PersonalData\Domain\Port\OrganizationsOfContacts;
use Prospecter\Core\PersonalData\Domain\Port\Readers;
use Prospecter\Core\SharedKernel\Application\QueryHandler;
use Prospecter\Core\SharedKernel\Domain\Contact\Email;
use Prospecter\Core\SharedKernel\Domain\Contact\PhoneNumber;
use Prospecter\Core\SharedKernel\Domain\Identity\AccountId;
use Prospecter\Core\SharedKernel\Domain\Identity\MemberId;
use Prospecter\Core\SharedKernel\Domain\Link\ExternalReference;

final readonly class IdentifyPersonsHandler implements QueryHandler
{
    public function __construct(
        private DesignatedContactRetriever $designatedContactRetriever,
        private PersonCardRetriever $personCardRetriever,
        private OrganizationsOfContacts $organizationsOfContacts,
        private Readers $readers,
    ) {
    }

    public function __invoke(IdentifyPersons $query, IdentifyPersonsOutput $output): void
    {
        $reader = $this->readers->readerFrom(new MemberId($query->readerId));

        $output->present(array_map(fn (PersonClues $clues): Identification => $this->identify($reader, $clues), $query->clues));
    }

    private function identify(Reader $reader, PersonClues $clues): Identification
    {
        $keys = PersonKeys::among($clues->links, $clues->emails, $clues->phoneNumbers);
        $candidates = $reader->identifiesByPersonalData() ? $this->named($this->designatedBy($reader->accountId, $keys), $reader) : [];

        return match (count($candidates)) {
            0 => $this->identifyByName($reader, $clues),
            1 => Identification::known($candidates[0]),
            default => Identification::toConfirm($candidates),
        };
    }

    private function identifyByName(Reader $reader, PersonClues $clues): Identification
    {
        $key = PersonResemblanceKey::of(new PersonName($clues->firstName, $clues->lastName));
        $namedAlike = null === $key ? [] : $this->named($this->ofTheirOrganizationsFirst($this->designatedContactRetriever->resembling($reader->accountId, $key), $clues->organizationIds, $reader->accountId), $reader);

        return [] === $namedAlike ? Identification::new() : Identification::toConfirm($namedAlike);
    }

    /**
     * @param list<PersonId> $contacts
     * @param list<string>   $organizationIds
     *
     * @return list<PersonId> the contacts of these organizations first
     */
    private function ofTheirOrganizationsFirst(array $contacts, array $organizationIds, AccountId $accountId): array
    {
        $organizationsOfContacts = [] === $organizationIds || [] === $contacts ? [] : $this->organizationsOfContacts->organizationsOf($contacts, $accountId);
        $worksThere = static fn (PersonId $contact): bool => array_any($organizationsOfContacts[$contact->value] ?? [], static fn (OrganizationId $organization): bool => in_array($organization->value, $organizationIds, true));
        usort($contacts, static fn (PersonId $one, PersonId $other): int => $worksThere($other) <=> $worksThere($one));

        return $contacts;
    }

    /**
     * Lists the contacts the keys designate, each one once, the surest key first.
     *
     * @return list<PersonId>
     */
    private function designatedBy(AccountId $accountId, PersonKeys $keys): array
    {
        $designated = array_map(static fn (PersonId $id): string => $id->value, $this->designatedByEachKey($accountId, $keys));

        return array_values(array_map(static fn (string $id): PersonId => new PersonId($id), array_unique($designated)));
    }

    /**
     * @return list<PersonId>
     */
    private function designatedByEachKey(AccountId $accountId, PersonKeys $keys): array
    {
        $retriever = $this->designatedContactRetriever;

        return array_values(array_filter([
            ...array_map(static fn (ExternalReference $reference): ?PersonId => $retriever->withReference($accountId, $reference), $keys->references),
            ...array_map(static fn (Email $email): ?PersonId => $retriever->withEmail($accountId, $email), $keys->emails),
            ...array_merge(...array_map(static fn (PhoneNumber $phoneNumber): array => $retriever->withPhoneNumber($accountId, $phoneNumber), $keys->phoneNumbers)),
        ]));
    }

    /**
     * @param list<PersonId> $contacts
     *
     * @return list<Candidate> in the contacts' order
     */
    private function named(array $contacts, Reader $reader): array
    {
        $cards = array_column(array_map(static fn (PersonCard $card): array => ['id' => $card->id, 'card' => $card], $this->personCardRetriever->ofIds($contacts, $reader)), 'card', 'id');
        $named = array_filter(array_map(static fn (PersonId $contact): ?PersonCard => $cards[$contact->value] ?? null, $contacts));

        return array_values(array_map(static fn (PersonCard $card): Candidate => new Candidate($card->id, trim($card->firstName.' '.$card->lastName)), $named));
    }
}
