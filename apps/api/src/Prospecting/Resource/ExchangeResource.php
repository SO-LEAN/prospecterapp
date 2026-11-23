<?php

declare(strict_types=1);

namespace App\Prospecting\Resource;

use App\Prospecting\Input\ExchangeInput;
use OpenApi\Attributes as OA;
use Symfony\Component\Serializer\Attribute\Context;
use Symfony\Component\Serializer\Attribute\SerializedName;
use Symfony\Component\Serializer\Normalizer\AbstractObjectNormalizer;

/**
 * A call, a message or a meeting with a contact or with the organization itself. It is done or planned.
 */
final readonly class ExchangeResource
{
    public const array STATUSES = ['planned', 'done', 'missed', 'cancelled'];
    public const string STATUS_DESCRIPTION = 'planned: still to come; done: it happened; missed: the contact did not come or did not answer; cancelled: called off before it happened';
    public const array CANCELLERS = ['prospector', 'contact'];
    public const array PROVIDERS = ['google'];
    public const string CREATED_BY_AN_AI_DESCRIPTION = 'An AI created it, acting as its member through the MCP server, never named';
    public const string IMPORTED_FROM_DESCRIPTION = 'The provider of the mailbox it was imported from: google for Gmail. Null for a logged exchange (ADR 0174)';

    /**
     * @param list<string> $organizationIds
     */
    public function __construct(
        public string $id,
        #[OA\Property(enum: ExchangeInput::CHANNELS)]
        public string $channel,
        #[OA\Property(enum: ExchangeInput::DIRECTIONS, description: 'inbound: the contact or the organization reached out; outbound: the member reached out')]
        public string $direction,
        #[OA\Property(format: 'date-time', description: 'When it happened, or when it is to happen if it is planned')]
        public string $occurredAt,
        #[OA\Property(enum: self::STATUSES, description: self::STATUS_DESCRIPTION)]
        public string $status,
        #[OA\Property(enum: self::CANCELLERS, description: 'Who called it off, if it is cancelled and someone said who')]
        public ?string $canceller,
        #[OA\Property(description: 'How long it lasts, if it is planned')]
        public ?int $durationInMinutes,
        public ?string $text,
        #[OA\Property(description: 'Where a meeting is held, written freely. Null for any other channel')]
        public ?string $place,
        #[OA\Property(description: 'The web address that joins a video call. Null for any other channel, or until it is known')]
        public ?string $link,
        #[OA\Property(description: 'Its text may hold personal data. A prospector without the right to manage personal data reads it flagged')]
        public bool $textFlagged,
        #[OA\Property(description: 'Its contact is forgotten, and its text may still hold their personal data until someone declares it redacted')]
        public bool $textToRedact,
        #[OA\Property(description: 'Its contact is forgotten, and its text or its files may still hold their personal data')]
        public bool $toRedact,
        #[OA\Property(description: 'The organizations it is kept for: those the contact was part of when it was logged, or the organization itself')]
        public array $organizationIds,
        #[OA\Property(description: 'The number of files attached to it')]
        public int $attachmentCount,
        #[OA\Property(description: 'The campaign it was logged in. Null for an exchange outside any campaign')]
        public ?CampaignOfExchangeResource $campaign,
        #[OA\Property(description: 'Its contact waits for its holder to attach them to an organization. Until then, no organization keeps it (ADR 0146)')]
        public bool $contactToAttach,
        #[OA\Property(description: 'Its contact booked it through its holder\'s public link (ADR 0164)')]
        public bool $bookedByLink,
        #[OA\Property(enum: self::PROVIDERS, description: self::IMPORTED_FROM_DESCRIPTION)]
        public ?string $importedFrom,
        #[OA\Property(description: self::CREATED_BY_AN_AI_DESCRIPTION)]
        public bool $createdByAnAI,
        #[SerializedName('_links')]
        #[Context([AbstractObjectNormalizer::SKIP_NULL_VALUES => true])]
        public ExchangeLinksResource $links,
    ) {
    }
}
