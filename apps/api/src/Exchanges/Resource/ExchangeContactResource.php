<?php

namespace App\Exchanges\Resource;

use App\PersonalData\Resource\PersonNameResource;

/**
 * The contact the exchange was with.
 */
final readonly class ExchangeContactResource
{
    public function __construct(
        public string $id,
        public PersonNameResource $name,
    ) {
    }
}
