<?php

namespace App\Identification\Resource;

/**
 * The persons and the organizations identified, each in the order given.
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
