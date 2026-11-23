<?php

declare(strict_types=1);

namespace App\Prospecting\Resource;

use App\Shared\Resource\AddressResource;
use OpenApi\Attributes as OA;

/**
 * Where to meet a contact. Prospecter proposes it while a member plans a meeting with them.
 */
final readonly class MeetingPlaceResource
{
    public function __construct(
        #[OA\Property(description: 'The address of the contact\'s first organization that has one. Null when none of their organizations has one')]
        public ?AddressResource $address,
    ) {
    }
}
