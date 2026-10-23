<?php

namespace App\Exchanges\Resource;

use App\Contacts\Resource\InterlocutorResource;
use App\Prospecting\Input\ExchangeInput;
use OpenApi\Attributes as OA;
use Symfony\Component\Serializer\Attribute\Context;
use Symfony\Component\Serializer\Attribute\SerializedName;
use Symfony\Component\Serializer\Normalizer\AbstractObjectNormalizer;

/**
 * The whole of an exchange with a contact or with the organization itself: who noted it, who corrected it last, and
 * the files joined to it.
 */
final readonly class ExchangeDetailResource
{
    /**
     * @param list<string>             $organizationIds
     * @param list<AttachmentResource> $attachments
     */
    public function __construct(
        public string $id,
        public InterlocutorResource $interlocutor,
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
        public StampResource $noted,
        #[OA\Property(description: 'The last correction, null while it is as it was noted')]
        public ?StampResource $corrected,
        #[OA\Property(description: 'Who declared it made conform, and when; null while it is not')]
        public ?StampResource $madeConform,
        #[OA\Property(description: 'The files joined to it, in the order they were')]
        public array $attachments,
        #[OA\Property(description: 'How many files are joined to it but hidden to a prospector reading it without the right to manage personal data')]
        public int $hiddenAttachments,
        #[SerializedName('_links')]
        #[Context([AbstractObjectNormalizer::SKIP_NULL_VALUES => true])]
        public ExchangeDetailLinksResource $links,
    ) {
    }
}
