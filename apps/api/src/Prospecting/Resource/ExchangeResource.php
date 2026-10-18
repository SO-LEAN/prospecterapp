<?php

namespace App\Prospecting\Resource;

use App\Prospecting\Input\ExchangeInput;
use OpenApi\Attributes as OA;

/**
 * A call, a message or a meeting with a contact.
 */
final readonly class ExchangeResource
{
    /**
     * @param list<string> $organizationIds
     */
    public function __construct(
        public string $id,
        #[OA\Property(enum: ExchangeInput::CHANNELS)]
        public string $channel,
        #[OA\Property(enum: ExchangeInput::DIRECTIONS, description: 'inbound: the contact reached out; outbound: we did')]
        public string $direction,
        #[OA\Property(description: 'A call that did not go through')]
        public bool $missed,
        #[OA\Property(format: 'date-time')]
        public string $occurredAt,
        public ?string $text,
        #[OA\Property(description: 'The organizations it is kept for, those the contact was part of when it was noted')]
        public array $organizationIds,
    ) {
    }
}
