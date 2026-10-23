<?php

namespace App\Prospecting\Resource;

use App\Prospecting\Input\ExchangeInput;
use OpenApi\Attributes as OA;
use Symfony\Component\Serializer\Attribute\SerializedName;

/**
 * A call, a message or a meeting with a contact, or with the organization itself.
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
        #[OA\Property(enum: ExchangeInput::DIRECTIONS, description: 'inbound: the contact or the organization reached out; outbound: we did')]
        public string $direction,
        #[OA\Property(description: 'A call that did not go through')]
        public bool $missed,
        #[OA\Property(format: 'date-time')]
        public string $occurredAt,
        public ?string $text,
        #[OA\Property(description: 'Its text may hold personal data: flagged to a prospector reading it without the right to manage them')]
        public bool $textFlagged,
        #[OA\Property(description: 'Its contact forgotten, its text may still hold their personal data, until it is declared made conform')]
        public bool $textToMakeConform,
        #[OA\Property(description: 'Its contact forgotten, its text or its files may still hold their personal data')]
        public bool $toMakeConform,
        #[OA\Property(description: 'The organizations it is kept for: those the contact was part of when it was noted, or the organization itself')]
        public array $organizationIds,
        #[OA\Property(description: 'How many files are joined to it')]
        public int $attachmentCount,
        #[SerializedName('_links')]
        public ExchangeLinksResource $links,
    ) {
    }
}
