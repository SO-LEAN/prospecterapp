<?php

declare(strict_types=1);

namespace App\Prospecting\Persistence;

use App\Shared\Persistence\Documents;
use App\Shared\Persistence\Table;
use Prospecter\Core\Prospecting\Application\PossibleDuplicate;

/**
 * The organizations the organizations of documents may be duplicates of, by their name, read in one batch from the
 * items `DynamoOrganizationRepository` writes: one no longer prospected is absent.
 */
final class PossibleDuplicates
{
    /**
     * @param list<array<string, mixed>> $documents
     *
     * @return array<string, list<PossibleDuplicate>> those of each document, by its id
     */
    public static function of(Table $prospectingTable, array $documents): array
    {
        $ids = array_values(array_unique(array_merge(...array_map(self::idsOf(...), $documents))));
        $named = [];

        foreach ($prospectingTable->items(array_map(static fn (string $id): array => [OrganizationKeys::organization($id), OrganizationKeys::ORGANIZATION], $ids)) as $item) {
            $named[$item['organization']['id']] = new PossibleDuplicate($item['organization']['id'], Documents::legalIdentityOf($item['organization']['legalIdentity'])->fullName());
        }

        return array_combine(
            array_column($documents, 'id'),
            array_map(static fn (array $document): array => array_values(array_filter(array_map(static fn (string $id): ?PossibleDuplicate => $named[$id] ?? null, self::idsOf($document)))), $documents),
        );
    }

    /**
     * @param array<string, mixed> $document
     *
     * @return list<string>
     */
    private static function idsOf(array $document): array
    {
        return $document['possibleDuplicates'] ?? [];
    }
}
