<?php

namespace App\Prospecting\Resource;

use App\Prospecting\Input\ExchangeInput;
use OpenApi\Attributes as OA;
use Symfony\Component\Serializer\Attribute\SerializedName;

/**
 * A call, a message or a meeting with a contact, or with the organization itself, done or planned.
 */
final readonly class ExchangeResource
{
    public const array STATUSES = ['planned', 'done', 'missed', 'cancelled'];
    public const string STATUS_DESCRIPTION = 'planned: still to come; done: it happened; missed: the contact did not come or answer; cancelled: called off before it happened';
    public const array CANCELLERS = ['prospector', 'contact'];

    /**
     * @param list<string> $organizationIds
     */
    public function __construct(
        public string $id,
        #[OA\Property(enum: ExchangeInput::CHANNELS)]
        public string $channel,
        #[OA\Property(enum: ExchangeInput::DIRECTIONS, description: 'inbound: the contact or the organization reached out; outbound: we did')]
        public string $direction,
        #[OA\Property(format: 'date-time', description: 'When it happened, or is to happen once planned')]
        public string $occurredAt,
        #[OA\Property(enum: self::STATUSES, description: self::STATUS_DESCRIPTION)]
        public string $status,
        #[OA\Property(enum: self::CANCELLERS, description: 'Who called it off once cancelled, when said')]
        public ?string $canceller,
        #[OA\Property(description: 'How long it lasts, once planned')]
        public ?int $durationInMinutes,
        public ?string $text,
        #[OA\Property(description: 'Where a meeting is held, written freely; null for any other channel')]
        public ?string $place,
        #[OA\Property(description: 'The web address a video call is joined by; null for any other channel, or until it is known')]
        public ?string $link,
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
