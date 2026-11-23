<?php

declare(strict_types=1);

namespace App\PersonalData\Persistence;

use App\Shared\Persistence\Documents;
use App\Shared\Persistence\Table;
use App\Shared\Typed;
use Prospecter\Core\PersonalData\Application\Port\MemberPresentationRetriever;
use Prospecter\Core\PersonalData\Application\PresentMembers\MemberPresentation;
use Prospecter\Core\SharedKernel\Application\FileResponse;
use Prospecter\Core\SharedKernel\Domain\PersonId;

/**
 * Reads the items `PERSON#<id>` / `PERSON` that `DynamoPersonRepository` writes. It does not restore the persons
 * (ADR 0088). It reads their name and picture unmasked, and their purpose. The purpose decides whether they present
 * themselves (ADR 0153).
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
        $items = $this->personalDataTable->items(array_map(static fn (PersonId $id): array => [PersonalDataKeys::person($id->value), PersonalDataKeys::PERSON], $ids));

        return array_map(static fn (array $item): MemberPresentation => self::presentationOf(Typed::document($item['person'])), $items);
    }

    /**
     * @param array<string, mixed> $document
     */
    private static function presentationOf(array $document): MemberPresentation
    {
        $name = Typed::document($document['name']);

        return new MemberPresentation(Typed::string($document['id']), Typed::stringOrNull($name['firstName'] ?? null), Typed::stringOrNull($name['lastName'] ?? null), FileResponse::orNone(Documents::fileOf($document['picture'])), Typed::string($document['purpose']));
    }
}
