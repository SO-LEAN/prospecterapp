<?php

declare(strict_types=1);

namespace App\Contacts\Resource;

use OpenApi\Attributes as OA;

/**
 * Until when a contact waits for the member reading their page, who met them, to attach them (ADR 0146).
 */
final readonly class ToAttachResource
{
    public function __construct(
        #[OA\Property(description: 'Their last meeting with the member, past or to come')]
        public \DateTimeImmutable $lastMeetingAt,
        #[OA\Property(description: 'The moment they are erased from if nobody attaches them')]
        public \DateTimeImmutable $erasableFrom,
    ) {
    }
}
