<?php

declare(strict_types=1);

namespace App\Contacts\Resource;

use App\Prospecting\Resource\ExchangeResource;
use OpenApi\Attributes as OA;

/**
 * An exchange of the day of the member, who it is with, and the organization of the contact.
 */
final readonly class DayExchangeResource
{
    public function __construct(
        public ExchangeResource $exchange,
        public InterlocutorResource $interlocutor,
        #[OA\Property(description: 'The organization of the contact the exchange is kept for; none with the organization itself, or for a contact no longer part of it')]
        public ?OrganizationOfContactResource $organization,
    ) {
    }
}
