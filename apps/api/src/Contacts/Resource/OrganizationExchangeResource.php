<?php

declare(strict_types=1);

namespace App\Contacts\Resource;

use App\Prospecting\Resource\ExchangeResource;

/**
 * An exchange on an organization's timeline, and who took part.
 */
final readonly class OrganizationExchangeResource
{
    public function __construct(
        public ExchangeResource $exchange,
        public InterlocutorResource $interlocutor,
    ) {
    }
}
