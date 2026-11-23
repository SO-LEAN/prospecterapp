<?php

declare(strict_types=1);

namespace App\PersonalData\Persistence;

use App\Shared\Persistence\Documents;
use App\Shared\Persistence\Revision;
use App\Shared\Persistence\Table;
use App\Shared\Typed;
use Prospecter\Core\PersonalData\Application\Port\ContactFactsRetriever;
use Prospecter\Core\PersonalData\Contract\Service\ListContactFacts\ContactFactsResponse;
use Prospecter\Core\PersonalData\Domain\Model\PersonId;
use Prospecter\Core\PersonalData\Domain\Model\Purpose;
use Prospecter\Core\SharedKernel\Domain\Identity\AccountId;
use Prospecter\Core\SharedKernel\Domain\Search\Slice;

/**
 * Reads the contacts' facts and their revision in the items `PERSON#<id>` / `PERSON` that `DynamoPersonRepository`
 * writes. It does not restore the persons (ADR 0088). It reads a contact by key, as the open unit of work is about to
 * write it. It also lists the persons the account keeps for prospecting, in `GSI1`. It tells how a contact is reached
 * from their contact details and from a LinkedIn profile among their links. It never publishes the contact details
 * or the links themselves.
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
            Purpose::Prospecting->value === Typed::document($item['person'])['purpose'] => self::factsOf($item),
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
        $person = Typed::document($item['person']);
        $contactDetails = Documents::contactDetailsOf($person['contactDetails']);

        return new ContactFactsResponse(
            Typed::string($person['id']),
            Revision::of($item),
            true,
            null !== $contactDetails->email,
            null !== $contactDetails->phoneNumber,
            true === $contactDetails->phoneNumber?->isMobile(),
            PersonDocument::isOnLinkedIn($person),
        );
    }
}
