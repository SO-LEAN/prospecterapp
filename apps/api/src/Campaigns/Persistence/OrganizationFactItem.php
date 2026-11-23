<?php

declare(strict_types=1);

namespace App\Campaigns\Persistence;

use App\Shared\Typed;

/**
 * What an organization's fact item says about where it is (ADR 0142). `DynamoFactRepository` writes it and the
 * retrievers read it, so they all share one format.
 */
final class OrganizationFactItem
{
    /**
     * @param array<string, mixed> $item
     *
     * @return array{municipalityCode: ?string, country: ?string} both null in a fact recorded before the facts carried them
     */
    public static function localityIn(array $item): array
    {
        return ['municipalityCode' => Typed::stringOrNull($item['municipalityCode'] ?? null), 'country' => Typed::stringOrNull($item['country'] ?? null)];
    }
}
