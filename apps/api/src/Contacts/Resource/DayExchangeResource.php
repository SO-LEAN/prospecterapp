<?php

declare(strict_types=1);

namespace App\Contacts\Resource;

use App\Prospecting\Resource\ExchangeResource;
use OpenApi\Attributes as OA;

/**
 * An exchange on the member's day, who it is with, and the contact's organization.
 */
final readonly class DayExchangeResource
{
    public function __construct(
        public ExchangeResource $exchange,
        public InterlocutorResource $interlocutor,
        #[OA\Property(
            description: 'The organization of the contact the exchange is kept for. An exchange with the organization itself has none, and so does a contact who is no longer part of it'
        )]
        public ?OrganizationOfContactResource $organization,
    ) {
    }
}
