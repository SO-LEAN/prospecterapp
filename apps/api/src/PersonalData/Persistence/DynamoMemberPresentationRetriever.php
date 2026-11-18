<?php

namespace App\PersonalData\Persistence;

use App\Shared\Persistence\Documents;
use App\Shared\Persistence\Table;
use Prospecter\Core\PersonalData\Application\Port\MemberPresentationRetriever;
use Prospecter\Core\PersonalData\Application\PresentMembers\MemberPresentation;
use Prospecter\Core\PersonalData\Domain\Model\Purpose;
use Prospecter\Core\PersonalData\Domain\Policy\MemberPresentationPolicy;
use Prospecter\Core\SharedKernel\Application\FileResponse;
use Prospecter\Core\SharedKernel\Domain\PersonId;

/**
 * Reads the items `PERSON#<id>` / `PERSON` that `DynamoPersonRepository` writes, without restoring the persons
 * (ADR 0088): those the policy presents, their name and picture in clear (ADR 0153).
 */
final readonly class DynamoMemberPresentationRetriever implements MemberPresentationRetriever
{
    public function __construct(private Table $personalDataTable)
    {
    }

    /**
     * @param list<PersonId> $ids
     */
    public function ofIds(array $ids): array
    {
        $documents = array_map(static fn (array $item): array => $item['person'], $this->personalDataTable->items(array_map(static fn (PersonId $id): array => [PersonalDataKeys::person($id->value), PersonalDataKeys::PERSON], $ids)));
        $presented = array_filter($documents, static fn (array $document): bool => MemberPresentationPolicy::presents(Purpose::from($document['purpose'])));

        return array_values(array_map(self::presentationOf(...), $presented));
    }

    /**
     * @param array<string, mixed> $document
     */
    private static function presentationOf(array $document): MemberPresentation
    {
        return new MemberPresentation($document['id'], $document['name']['firstName'] ?? null, $document['name']['lastName'] ?? null, FileResponse::orNone(Documents::fileOf($document['picture'])));
    }
}
