<?php

declare(strict_types=1);

namespace App\Identification\Resource;

/**
 * The identified persons and organizations, each list in the order given.
 */
final readonly class IdentificationsResource
{
    /**
     * @param list<IdentificationResource> $persons
     * @param list<IdentificationResource> $organizations
     */
    public function __construct(
        public array $persons,
        public array $organizations,
    ) {
    }
}
