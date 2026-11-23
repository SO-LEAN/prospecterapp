<?php

declare(strict_types=1);

namespace App\Identification;

use App\Identification\Resource\CandidateResource;
use App\Identification\Resource\RecordedResource;
use App\Identification\Resource\RecordingResource;
use App\PersonalData\Input\CollectContactInput;
use App\PersonalData\Input\PersonContactDetailsInput;
use App\PersonalData\Input\PersonNameInput;
use App\Prospecting\Input\OrganizationContactDetailsInput;
use App\Prospecting\Input\StartProspectingInput;
use App\Prospecting\OrganizationResolver;
use App\Shared\CommandBus;
use App\Shared\Identities;
use App\Shared\Input\AddressInput;
use App\Shared\Input\LegalIdentityInput;
use App\Shared\Mcp\ToolInputs;
use Prospecter\Core\PersonalData\Application\CompareWithContact\CompareWithContact;
use Prospecter\Core\PersonalData\Application\CompareWithContact\KeptComparison as KeptContactComparison;
use Prospecter\Core\PersonalData\Application\CompleteContact\CompleteContact;
use Prospecter\Core\PersonalData\Application\IdentifyPersons\Candidate as PersonCandidate;
use Prospecter\Core\PersonalData\Application\IdentifyPersons\Identification as PersonIdentification;
use Prospecter\Core\PersonalData\Application\IdentifyPersons\IdentifyPersons;
use Prospecter\Core\PersonalData\Application\IdentifyPersons\KeptIdentifications as KeptPersonIdentifications;
use Prospecter\Core\PersonalData\Application\IdentifyPersons\PersonClues;
use Prospecter\Core\Prospecting\Application\AddContact\AddContact;
use Prospecter\Core\Prospecting\Application\CompareWithOrganization\CompareWithOrganization;
use Prospecter\Core\Prospecting\Application\CompareWithOrganization\KeptComparison as KeptOrganizationComparison;
use Prospecter\Core\Prospecting\Application\CompleteOrganization\CompleteOrganization;
use Prospecter\Core\Prospecting\Application\IdentifyOrganizations\Candidate as OrganizationCandidate;
use Prospecter\Core\Prospecting\Application\IdentifyOrganizations\Identification as OrganizationIdentification;
use Prospecter\Core\Prospecting\Application\IdentifyOrganizations\IdentifyOrganizations;
use Prospecter\Core\Prospecting\Application\IdentifyOrganizations\KeptIdentifications as KeptOrganizationIdentifications;
use Prospecter\Core\Prospecting\Application\IdentifyOrganizations\OrganizationClues;
use Prospecter\Core\Prospecting\Application\StopProspecting\StopProspecting;
use Prospecter\Core\SharedKernel\Application\Comparison;
use Prospecter\Core\SharedKernel\Application\QueryHandler;
use Symfony\Component\Clock\ClockInterface;

/**
 * Records a contact and the organization they work for, in one AI gesture. It composes Prospecting's and
 * PersonalData's use cases (ADR 0177). It reads everything first and finds what exists by its keys. Then it creates
 * what is missing, completes what is empty and attaches the contact. Each use case runs in its own unit of work.
 *
 * @phpstan-type RecordedPerson array{firstName?: string, lastName?: string, title?: string, jobTitle?: string, email?: string, phoneNumber?: string, links?: list<string>}
 * @phpstan-type RecordedOrganization array{name?: string, siret?: string, links?: list<string>, website?: string, email?: string, phoneNumber?: string, city?: string}
 */
final readonly class ContactRecorder
{
    /** A person's title when no clue says how to address them. */
    private const string UNKNOWN_CIVILITY = 'x';

    public function __construct(
        private QueryHandler $identifyOrganizations,
        private QueryHandler $identifyPersons,
        private QueryHandler $compareWithOrganization,
        private QueryHandler $compareWithContact,
        private OrganizationResolver $organizations,
        private ToolInputs $inputs,
        private Identities $identities,
        private CommandBus $commandBus,
        private ClockInterface $clock,
    ) {
    }

    /**
     * @param RecordedPerson       $person
     * @param RecordedOrganization $organization
     * @param Designation          $theOrganization the caller has already checked the access to the retained organization
     */
    public function record(string $memberId, array $person, array $organization, Designation $theOrganization, Designation $thePerson): RecordingResource
    {
        $readOrganization = $this->readOrganization($memberId, $organization, $theOrganization);
        $readPerson = $this->readPerson($memberId, $person, $readOrganization->id, $thePerson);

        if (RecordedResource::TO_CONFIRM === $readOrganization->status || RecordedResource::TO_CONFIRM === $readPerson->status) {
            return new RecordingResource($readOrganization, $readPerson);
        }

        $contactWrite = $this->contactWrite($memberId, $person, $readPerson->id);

        return null === $readOrganization->id
            ? $this->withTheOrganizationCreated($memberId, $organization, $contactWrite)
            : new RecordingResource($this->completedOrganization($memberId, $readOrganization->id, $organization), $contactWrite($readOrganization->id));
    }

    /**
     * Creates the organization, then writes the contact. When a step fails, the account stops prospecting the
     * organization the call created, through the domain gesture that erases it. The failure is then thrown again
     * (ADR 0177).
     *
     * @param RecordedOrganization               $organization
     * @param \Closure(string): RecordedResource $contactWrite
     */
    private function withTheOrganizationCreated(string $memberId, array $organization, \Closure $contactWrite): RecordingResource
    {
        $starting = $this->startingOf($organization);
        $created = $this->identities->next();
        $this->commandBus->dispatch($starting->commandOfAnAI($memberId, $created, $this->clock->now()));

        try {
            $this->noteTheSiretOf($memberId, $created, $organization);

            return new RecordingResource(new RecordedResource(RecordedResource::CREATED, $created), $contactWrite($created));
        } catch (\Throwable $failure) {
            $this->commandBus->dispatch(new StopProspecting($memberId, $created, $this->clock->now()));

            throw $failure;
        }
    }

    /**
     * Prepares the contact's write for their organization. It reads everything it needs and checks its input before
     * any write. So an invalid person creates nothing to undo.
     *
     * @param RecordedPerson $person
     *
     * @return \Closure(string): RecordedResource
     */
    private function contactWrite(string $memberId, array $person, ?string $personId): \Closure
    {
        if (null === $personId) {
            $this->ensureTheContactIsValid($person);

            return fn (string $organizationId): RecordedResource => $this->collectedContact($memberId, $person, $organizationId);
        }

        $comparison = $this->contactComparison($memberId, $personId, $person);

        return fn (string $organizationId): RecordedResource => $this->completedContact($memberId, $personId, $person, $comparison, $organizationId);
    }

    /**
     * @param RecordedPerson $person
     */
    private function ensureTheContactIsValid(array $person): void
    {
        $this->collectingOf($person, []);
    }

    /**
     * Reads the organization as the call designates it, and writes nothing. The organization is known, new, or to
     * confirm among its candidates.
     *
     * @param RecordedOrganization $organization
     */
    private function readOrganization(string $memberId, array $organization, Designation $designation): RecordedResource
    {
        if (null !== $designation->retained) {
            return new RecordedResource(RecordedResource::KNOWN, $designation->retained);
        }

        if ($designation->isAnotherOne()) {
            return new RecordedResource(RecordedResource::NEW);
        }

        $identified = new KeptOrganizationIdentifications();
        $this->identifyOrganizations->__invoke(new IdentifyOrganizations($memberId, [self::organizationClues($organization)]), $identified);

        return self::read($identified->identifications[0]);
    }

    /**
     * Reads the person as the call designates them, among the organization's contacts first, and writes nothing.
     *
     * @param RecordedPerson $person
     */
    private function readPerson(string $memberId, array $person, ?string $organizationId, Designation $designation): RecordedResource
    {
        if (null !== $designation->retained) {
            return new RecordedResource(RecordedResource::KNOWN, $designation->retained);
        }

        if ($designation->isAnotherOne()) {
            return new RecordedResource(RecordedResource::NEW);
        }

        $identified = new KeptPersonIdentifications();
        $this->identifyPersons->__invoke(new IdentifyPersons($memberId, [self::personClues($person, $organizationId)]), $identified);

        return self::read($identified->identifications[0]);
    }

    private static function read(OrganizationIdentification|PersonIdentification $identification): RecordedResource
    {
        return match ($identification->status) {
            OrganizationIdentification::KNOWN => new RecordedResource(RecordedResource::KNOWN, $identification->candidates[0]->id),
            OrganizationIdentification::TO_CONFIRM => new RecordedResource(RecordedResource::TO_CONFIRM, candidates: array_map(
                static fn (OrganizationCandidate|PersonCandidate $candidate): CandidateResource => new CandidateResource($candidate->id, $candidate->name),
                $identification->candidates,
            )),
            default => new RecordedResource(RecordedResource::NEW),
        };
    }

    /**
     * @param RecordedOrganization $organization
     */
    private function completedOrganization(string $memberId, string $organizationId, array $organization): RecordedResource
    {
        $compared = new KeptOrganizationComparison();
        $this->compareWithOrganization->__invoke(new CompareWithOrganization(id: $organizationId, siret: $organization['siret'] ?? null, links: $organization['links'] ?? [], website: $organization['website'] ?? null, email: $organization['email'] ?? null, phoneNumber: $organization['phoneNumber'] ?? null), $compared);
        $comparison = $compared->comparison;

        if ([] !== $comparison->completed) {
            $this->commandBus->dispatch(new CompleteOrganization(prospectorId: $memberId, id: $organizationId, siret: $organization['siret'] ?? null, links: $organization['links'] ?? [], website: $organization['website'] ?? null, email: $organization['email'] ?? null, phoneNumber: $organization['phoneNumber'] ?? null, executedAt: $this->clock->now()));
        }

        return new RecordedResource(RecordedResource::KNOWN, $organizationId, completed: $comparison->completed, differing: $comparison->differing);
    }

    /**
     * @param RecordedPerson $person
     */
    private function contactComparison(string $memberId, string $personId, array $person): Comparison
    {
        $compared = new KeptContactComparison();
        $this->compareWithContact->__invoke(new CompareWithContact(readerId: $memberId, id: $personId, civility: $person['title'] ?? self::UNKNOWN_CIVILITY, jobTitle: $person['jobTitle'] ?? null, email: $person['email'] ?? null, phoneNumber: $person['phoneNumber'] ?? null, links: $person['links'] ?? []), $compared);

        return $compared->comparison;
    }

    /**
     * Logs the SIRET of a created organization. Starting to prospect an organization does not take a SIRET.
     *
     * @param RecordedOrganization $organization
     */
    private function noteTheSiretOf(string $memberId, string $organizationId, array $organization): void
    {
        if (isset($organization['siret'])) {
            $this->commandBus->dispatch(new CompleteOrganization(prospectorId: $memberId, id: $organizationId, siret: $organization['siret'], links: [], website: null, email: null, phoneNumber: null, executedAt: $this->clock->now()));
        }
    }

    /**
     * @param RecordedPerson $person
     */
    private function collectedContact(string $memberId, array $person, string $organizationId): RecordedResource
    {
        $id = $this->identities->next();
        $this->commandBus->dispatch($this->collectingOf($person, [$organizationId])->commandOfAnAI($memberId, $id, $this->clock->now()));

        return new RecordedResource(RecordedResource::CREATED, $id);
    }

    /**
     * @param RecordedPerson $person
     */
    private function completedContact(string $memberId, string $personId, array $person, Comparison $comparison, string $organizationId): RecordedResource
    {
        if ([] !== $comparison->completed) {
            $this->commandBus->dispatch(new CompleteContact(readerId: $memberId, id: $personId, civility: $person['title'] ?? self::UNKNOWN_CIVILITY, jobTitle: $person['jobTitle'] ?? null, email: $person['email'] ?? null, phoneNumber: $person['phoneNumber'] ?? null, links: $person['links'] ?? [], executedAt: $this->clock->now()));
        }

        if (!in_array($personId, $this->organizations->read($organizationId)->contactIds, true)) {
            $this->commandBus->dispatch(new AddContact(prospectorId: $memberId, id: $organizationId, personId: $personId, executedAt: $this->clock->now()));
        }

        return new RecordedResource(RecordedResource::KNOWN, $personId, completed: $comparison->completed, differing: $comparison->differing);
    }

    /**
     * @param RecordedOrganization $organization
     */
    private static function organizationClues(array $organization): OrganizationClues
    {
        return new OrganizationClues(
            name: $organization['name'] ?? null,
            siret: $organization['siret'] ?? null,
            links: $organization['links'] ?? [],
            website: $organization['website'] ?? null,
            emails: isset($organization['email']) ? [$organization['email']] : [],
            city: $organization['city'] ?? null,
        );
    }

    /**
     * @param RecordedPerson $person
     */
    private static function personClues(array $person, ?string $organizationId): PersonClues
    {
        return new PersonClues(
            firstName: $person['firstName'] ?? null,
            lastName: $person['lastName'] ?? null,
            links: $person['links'] ?? [],
            emails: isset($person['email']) ? [$person['email']] : [],
            phoneNumbers: isset($person['phoneNumber']) ? [$person['phoneNumber']] : [],
            organizationIds: null === $organizationId ? [] : [$organizationId],
        );
    }

    /**
     * @param RecordedOrganization $organization
     */
    private function startingOf(array $organization): StartProspectingInput
    {
        return $this->inputs->valid(new StartProspectingInput(
            new LegalIdentityInput($organization['name'] ?? null),
            new OrganizationContactDetailsInput($organization['email'] ?? null, $organization['phoneNumber'] ?? null, null, $organization['links'] ?? [], $organization['website'] ?? null),
            new AddressInput(city: $organization['city'] ?? null),
        ));
    }

    /**
     * @param RecordedPerson $person
     * @param list<string>   $organizationIds
     */
    private function collectingOf(array $person, array $organizationIds): CollectContactInput
    {
        return $this->inputs->valid(new CollectContactInput(
            new PersonNameInput($person['firstName'] ?? null, $person['lastName'] ?? null),
            $person['title'] ?? self::UNKNOWN_CIVILITY,
            $person['jobTitle'] ?? null,
            new PersonContactDetailsInput($person['email'] ?? null, $person['phoneNumber'] ?? null, null, $person['links'] ?? []),
            $organizationIds,
        ));
    }
}
