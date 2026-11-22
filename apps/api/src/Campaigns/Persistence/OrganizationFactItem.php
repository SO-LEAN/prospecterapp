<?php

declare(strict_types=1);

namespace App\Campaigns\Persistence;

/**
 * What the item of the fact of an organization says of where it is (ADR 0142), written by `DynamoFactRepository` and
 * read by the retrievers: one format for all.
 */
final class OrganizationFactItem
{
    /**
     * @param array<string, mixed> $item
     *
     * @return array{municipalityCode: ?string, country: ?string} both null in a fact recorded before they were
     */
    public static function localityIn(array $item): array
    {
        return ['municipalityCode' => $item['municipalityCode'] ?? null, 'country' => $item['country'] ?? null];
    }
}
