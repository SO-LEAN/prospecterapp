<?php

namespace App\PersonalData\Persistence;

use App\Shared\Persistence\Documents;
use App\Shared\Persistence\Revision;
use App\Shared\Persistence\Table;
use Prospecter\Core\PersonalData\Application\Port\ContactFactsRetriever;
use Prospecter\Core\PersonalData\Contract\Service\ListContactFacts\ContactFactsResponse;
use Prospecter\Core\PersonalData\Domain\Model\Purpose;
use Prospecter\Core\SharedKernel\Domain\AccountId;
use Prospecter\Core\SharedKernel\Domain\PersonId;
use Prospecter\Core\SharedKernel\Domain\Slice;

/**
 * Reads the facts of the contacts in the items `PERSON#<id>` / `PERSON` that `DynamoPersonRepository` writes,
 * their revision with them, without restoring them (ADR 0088): by key, as the open unit of work is about to write
 * them, and listed among the persons the account keeps for its prospecting in `GSI1`; how a contact is reached, read
 * from its contact details and its LinkedIn profile, never published themselves.
 */
final readonly class DynamoContactFactsRetriever implements ContactFactsRetriever
{
    public function __construct(private Table $personalDataTable)
    {
    }

    public function of(PersonId $id): ?ContactFactsResponse
    {
        $item = $this->personalDataTable->item(PersonalDataKeys::person($id->value), PersonalDataKeys::PERSON);

        return match (true) {
            null === $item => ContactFactsResponse::absent($id->value),
            Purpose::Prospecting->value === $item['person']['purpose'] => self::factsOf($item),
            default => null,
        };
    }

    public function sliceOf(AccountId $accountId, ?string $after, int $max): Slice
    {
        $items = $this->personalDataTable->query(PersonalDataKeys::contactsOf($accountId->value), Table::BY_GSI1, $after, $max + 1);

        return Documents::slice(array_map(self::factsOf(...), $items), $max, static fn (ContactFactsResponse $facts): string => $facts->personId);
    }

    /**
     * @param array<string, mixed> $item
     */
    private static function factsOf(array $item): ContactFactsResponse
    {
        $person = $item['person'];
        $contactDetails = Documents::contactDetailsOf($person['contactDetails']);

        return new ContactFactsResponse(
            $person['id'],
            Revision::of($item),
            true,
            null !== $contactDetails->email,
            null !== $contactDetails->phoneNumber,
            true === $contactDetails->phoneNumber?->isMobile(),
            null !== PersonDocument::linkedInProfileOf($person),
        );
    }
}
