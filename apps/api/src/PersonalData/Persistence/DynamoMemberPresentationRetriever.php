<?php

declare(strict_types=1);

namespace App\PersonalData\Persistence;

use App\Shared\Persistence\Documents;
use App\Shared\Persistence\Table;
use Prospecter\Core\PersonalData\Application\Port\MemberPresentationRetriever;
use Prospecter\Core\PersonalData\Application\PresentMembers\MemberPresentation;
use Prospecter\Core\SharedKernel\Application\FileResponse;
use Prospecter\Core\SharedKernel\Domain\PersonId;

/**
 * Reads the items `PERSON#<id>` / `PERSON` that `DynamoPersonRepository` writes, without restoring the persons
 * (ADR 0088): their name and picture in clear, and their purpose, which decides whether they present themselves (ADR
 * 0153).
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

        return array_map(self::presentationOf(...), $documents);
    }

    /**
     * @param array<string, mixed> $document
     */
    private static function presentationOf(array $document): MemberPresentation
    {
        return new MemberPresentation($document['id'], $document['name']['firstName'] ?? null, $document['name']['lastName'] ?? null, FileResponse::orNone(Documents::fileOf($document['picture'])), $document['purpose']);
    }
}
