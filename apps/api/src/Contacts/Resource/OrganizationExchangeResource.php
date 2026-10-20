<?php

namespace App\Contacts\Resource;

use App\Prospecting\Resource\ExchangeResource;

/**
 * An exchange in the timeline of an organization, and who communicated.
 */
final readonly class OrganizationExchangeResource
{
    public function __construct(
        public ExchangeResource $exchange,
        public InterlocutorResource $interlocutor,
    ) {
    }
}
