<?php

declare(strict_types=1);

namespace App\PersonalData\Persistence;

use App\Shared\Persistence\SearchIndexes;
use App\Shared\Persistence\Table;
use App\Shared\Typed;
use Prospecter\Core\PersonalData\Application\Port\DesignatedContactRetriever;
use Prospecter\Core\PersonalData\Domain\Model\PersonId;
use Prospecter\Core\PersonalData\Domain\Model\PersonResemblanceKey;
use Prospecter\Core\PersonalData\Domain\Model\Purpose;
use Prospecter\Core\SharedKernel\Domain\AccountId;
use Prospecter\Core\SharedKernel\Domain\Email;
use Prospecter\Core\SharedKernel\Domain\ExternalReference;
use Prospecter\Core\SharedKernel\Domain\PhoneNumber;

/**
 * Finds the account's contacts that a key designates. It reads the reservations, the phone number index and the
 * resemblance key links that `DynamoPersonRepository` writes. It does not restore the persons (ADR 0088).
 */
final readonly class DynamoDesignatedContactRetriever implements DesignatedContactRetriever
{
    public function __construct(private Table $personalDataTable)
    {
    }

    public function withReference(AccountId $accountId, ExternalReference $reference): ?PersonId
    {
        return $this->holderOf(PersonalDataKeys::reference($accountId, $reference), PersonalDataKeys::REFERENCE);
    }

    public function withEmail(AccountId $accountId, Email $email): ?PersonId
    {
        return $this->holderOf(PersonalDataKeys::email($accountId, Purpose::Prospecting, $email->value), PersonalDataKeys::EMAIL);
    }

    public function withPhoneNumber(AccountId $accountId, PhoneNumber $phoneNumber): array
    {
        $keys = SearchIndexes::withPhoneNumber($this->personalDataTable, PersonalDataKeys::scopeOf($accountId, Purpose::Prospecting), $phoneNumber);

        return array_map(static fn (string $key): PersonId => new PersonId(substr($key, strlen(PersonalDataKeys::person('')))), $keys);
    }

    public function resembling(AccountId $accountId, PersonResemblanceKey $key): array
    {
        $links = $this->personalDataTable->query(PersonalDataKeys::ofResemblance($accountId->value, $key->value));

        return array_map(static fn (array $link): PersonId => new PersonId(Typed::string($link['personId'])), $links);
    }

    private function holderOf(string $reservation, string $kind): ?PersonId
    {
        $holder = Typed::stringOrNull($this->personalDataTable->item($reservation, $kind)['ownerId'] ?? null);

        return null === $holder ? null : new PersonId($holder);
    }
}
