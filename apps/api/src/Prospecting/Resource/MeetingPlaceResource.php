<?php

namespace App\Prospecting\Resource;

use App\Shared\Resource\AddressResource;
use OpenApi\Attributes as OA;

/**
 * Where to meet a contact, proposed while planning a meeting with them.
 */
final readonly class MeetingPlaceResource
{
    public function __construct(
        #[OA\Property(description: 'The address of the first organization of the contact having one; none when no organization of theirs has')]
        public ?AddressResource $address,
    ) {
    }
}
