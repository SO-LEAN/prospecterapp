<?php

declare(strict_types=1);

namespace Prospecter\Core\Prospecting\Application\Organization\SearchEstablishments;

/**
 * Finds the public directory's establishments that an organization may be, so that a member records its SIRET
 * without typing it.
 */
final readonly class SearchEstablishments
{
    public function __construct(
        public string $organizationId,
    ) {
    }
}
