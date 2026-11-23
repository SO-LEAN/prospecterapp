<?php

declare(strict_types=1);

namespace App\Contacts\Resource;

use OpenApi\Attributes as OA;

/**
 * Until when a contact waits for the member who met them to attach them. That member is the one reading the
 * contact's page (ADR 0146).
 */
final readonly class ToAttachResource
{
    public function __construct(
        #[OA\Property(description: 'Their last meeting with the member, past or to come')]
        public \DateTimeImmutable $lastMeetingAt,
        #[OA\Property(description: 'The moment from which they are erased if nobody attaches them')]
        public \DateTimeImmutable $erasableFrom,
    ) {
    }
}
