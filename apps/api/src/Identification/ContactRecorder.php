<?php

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
 * Records a contact and the organization they work for in one gesture of an AI, composed of the use cases of
 * Prospecting and PersonalData (ADR 0177): everything read first, what exists found by its keys, then what lacks
 * created, what is empty completed, the contact attached, each use case in its own unit of work.
 *
 * @phpstan-type RecordedPerson array{firstName?: string, lastName?: string, civility?: string, jobTitle?: string, email?: string, phoneNumber?: string, links?: list<string>}
 * @phpstan-type RecordedOrganization array{name?: string, siret?: string, links?: list<string>, website?: string, email?: string, phoneNumber?: string, city?: string}
 */
final readonly class ContactRecorder
{
    /** How one addresses a person no clue says how. */
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
     * @param Designation          $theOrganization the access to the one retained checked
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
     * The organization created, then the contact written: a step failing, the organization the call created stops
     * being prospected, by the gesture of the domain that erases it, and the failure goes on (ADR 0177).
     *
     * @param RecordedOrganization               $organization
     * @param \Closure(string): RecordedResource $contactWrite
     */
    private function withTheOrganizationCreated(string $memberId, array $organization, \Closure $contactWrite): RecordingResource
    {
        $starting = $this->startingOf($organization);
        $created = $this->identities->next();
        $this->commandBus->dispatch($starting->commandOf($memberId, $created, $this->clock->now()));

        try {
            $this->noteTheSiretOf($memberId, $created, $organization);

            return new RecordingResource(new RecordedResource(RecordedResource::CREATED, $created), $contactWrite($created));
        } catch (\Throwable $failure) {
            $this->commandBus->dispatch(new StopProspecting($memberId, $created, $this->clock->now()));

            throw $failure;
        }
    }

    /**
     * The write of the contact for their organization, everything it reads read before any write.
     *
     * @param RecordedPerson $person
     *
     * @return \Closure(string): RecordedResource
     */
    private function contactWrite(string $memberId, array $person, ?string $personId): \Closure
    {
        if (null === $personId) {
            return fn (string $organizationId): RecordedResource => $this->collectedContact($memberId, $person, $organizationId);
        }

        $comparison = $this->contactComparison($memberId, $personId, $person);

        return fn (string $organizationId): RecordedResource => $this->completedContact($memberId, $personId, $person, $comparison, $organizationId);
    }

    /**
     * The organization as the call designates it, nothing written: known, new, or to confirm among its candidates.
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
        ($this->identifyOrganizations)(new IdentifyOrganizations($memberId, [self::organizationClues($organization)]), $identified);

        return self::read($identified->identifications[0]);
    }

    /**
     * The person as the call designates them, among the contacts of the organization first, nothing written.
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
        ($this->identifyPersons)(new IdentifyPersons($memberId, [self::personClues($person, $organizationId)]), $identified);

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
        ($this->compareWithOrganization)(new CompareWithOrganization($organizationId, $organization['siret'] ?? null, $organization['links'] ?? [], $organization['website'] ?? null, $organization['email'] ?? null, $organization['phoneNumber'] ?? null), $compared);
        $comparison = $compared->comparison;

        if ([] !== $comparison->completed) {
            $this->commandBus->dispatch(new CompleteOrganization($memberId, $organizationId, $organization['siret'] ?? null, $organization['links'] ?? [], $organization['website'] ?? null, $organization['email'] ?? null, $organization['phoneNumber'] ?? null, $this->clock->now()));
        }

        return new RecordedResource(RecordedResource::KNOWN, $organizationId, completed: $comparison->completed, differing: $comparison->differing);
    }

    /**
     * @param RecordedPerson $person
     */
    private function contactComparison(string $memberId, string $personId, array $person): Comparison
    {
        $compared = new KeptContactComparison();
        ($this->compareWithContact)(new CompareWithContact($memberId, $personId, $person['civility'] ?? self::UNKNOWN_CIVILITY, $person['jobTitle'] ?? null, $person['email'] ?? null, $person['phoneNumber'] ?? null, $person['links'] ?? []), $compared);

        return $compared->comparison;
    }

    /**
     * The SIRET of an organization created, which its start does not take.
     *
     * @param RecordedOrganization $organization
     */
    private function noteTheSiretOf(string $memberId, string $organizationId, array $organization): void
    {
        if (isset($organization['siret'])) {
            $this->commandBus->dispatch(new CompleteOrganization($memberId, $organizationId, $organization['siret'], [], null, null, null, $this->clock->now()));
        }
    }

    /**
     * @param RecordedPerson $person
     */
    private function collectedContact(string $memberId, array $person, string $organizationId): RecordedResource
    {
        $id = $this->identities->next();
        $this->commandBus->dispatch($this->collectingOf($person, [$organizationId])->commandOf($memberId, $id, $this->clock->now()));

        return new RecordedResource(RecordedResource::CREATED, $id);
    }

    /**
     * @param RecordedPerson $person
     */
    private function completedContact(string $memberId, string $personId, array $person, Comparison $comparison, string $organizationId): RecordedResource
    {
        if ([] !== $comparison->completed) {
            $this->commandBus->dispatch(new CompleteContact($memberId, $personId, $person['civility'] ?? self::UNKNOWN_CIVILITY, $person['jobTitle'] ?? null, $person['email'] ?? null, $person['phoneNumber'] ?? null, $person['links'] ?? [], $this->clock->now()));
        }

        if (!in_array($personId, $this->organizations->read($organizationId)->contactIds, true)) {
            $this->commandBus->dispatch(new AddContact($memberId, $organizationId, $personId, $this->clock->now()));
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
            $person['civility'] ?? self::UNKNOWN_CIVILITY,
            $person['jobTitle'] ?? null,
            new PersonContactDetailsInput($person['email'] ?? null, $person['phoneNumber'] ?? null, null, $person['links'] ?? []),
            $organizationIds,
        ));
    }
}
