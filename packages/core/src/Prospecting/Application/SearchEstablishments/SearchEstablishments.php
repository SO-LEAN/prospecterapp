<?php

declare(strict_types=1);

namespace Prospecter\Core\Prospecting\Application\SearchEstablishments;

/**
 * The establishments of the public directory an organization may be, to note its SIRET without typing it.
 */
final readonly class SearchEstablishments
{
    public function __construct(
        public string $organizationId,
    ) {
    }
}
