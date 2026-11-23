<?php

declare(strict_types=1);

namespace Tests\Core\Double;

use Prospecter\Core\PersonalData\Application\Port\ContactFactsRetriever;
use Prospecter\Core\PersonalData\Contract\Service\ListContactFacts\ContactFactsResponse;
use Prospecter\Core\PersonalData\Domain\Model\Person;
use Prospecter\Core\PersonalData\Domain\Model\Purpose;
use Prospecter\Core\SharedKernel\Domain\AccountId;
use Prospecter\Core\SharedKernel\Domain\ContactDetails;
use Prospecter\Core\SharedKernel\Domain\ExternalReferences;
use Prospecter\Core\SharedKernel\Domain\KnownSource;
use Prospecter\Core\SharedKernel\Domain\PersonId;
use Prospecter\Core\SharedKernel\Domain\SearchKeys;
use Prospecter\Core\SharedKernel\Domain\Slice;

/**
 * Reads the facts of the contacts that the in-memory repository saved, as the adapter reads the table's items. Each
 * contact's revision comes with its facts.
 */
final readonly class InMemoryContactFactsRetriever implements ContactFactsRetriever
{
    public function __construct(private InMemoryDatabase $database)
    {
    }

    public function of(PersonId $id): ?ContactFactsResponse
    {
        $person = $this->database->person((string) $id);

        return match (true) {
            null === $person => ContactFactsResponse::absent((string) $id),
            Purpose::Prospecting === $person->purpose() => $this->factsOf($person),
            default => null,
        };
    }

    public function sliceOf(AccountId $accountId, ?string $after, int $max): Slice
    {
        $contacts = array_values(array_filter($this->database->persons(), static fn (Person $person): bool => $person->isAContactOf($accountId)));
        $slice = NewestFirst::slice($contacts, static fn (Person $person): string => (string) $person->id(), static fn (): SearchKeys => new SearchKeys(null, null), null, $after, $max);

        return new Slice(array_map($this->factsOf(...), $slice->content), $slice->next);
    }

    private function factsOf(Person $person): ContactFactsResponse
    {
        $state = StateOf::the($person);
        $contactDetails = $state->object('contactDetails', ContactDetails::class);

        return new ContactFactsResponse(
            (string) $person->id(),
            $this->database->revisionOf((string) $person->id()),
            true,
            null !== $contactDetails->email,
            null !== $contactDetails->phoneNumber,
            true === $contactDetails->phoneNumber?->isMobile(),
            $state->object('references', ExternalReferences::class)->hasOneOf(KnownSource::LinkedInProfile),
        );
    }
}
