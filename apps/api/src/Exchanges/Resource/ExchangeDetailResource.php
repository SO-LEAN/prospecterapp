<?php

declare(strict_types=1);

namespace App\Exchanges\Resource;

use App\Contacts\Resource\InterlocutorResource;
use App\Prospecting\Input\ExchangeInput;
use App\Prospecting\Resource\CampaignOfExchangeResource;
use App\Prospecting\Resource\ExchangeResource;
use App\Shared\Resource\RevisionDescription;
use OpenApi\Attributes as OA;
use Symfony\Component\Serializer\Attribute\Context;
use Symfony\Component\Serializer\Attribute\SerializedName;
use Symfony\Component\Serializer\Normalizer\AbstractObjectNormalizer;

/**
 * An exchange with a contact, or with the organization itself, in full. It says who logged it, who last corrected
 * it, and which files are attached to it. It also gives the campaign it was logged in (ADR 0152).
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
        #[OA\Property(enum: ExchangeInput::DIRECTIONS, description: 'inbound: the contact or the organization reached out. outbound: the account reached out')]
        public string $direction,
        #[OA\Property(format: 'date-time', description: 'When it happened, or when it is to happen once planned')]
        public string $occurredAt,
        #[OA\Property(enum: ExchangeResource::STATUSES, description: ExchangeResource::STATUS_DESCRIPTION)]
        public string $status,
        #[OA\Property(enum: ExchangeResource::CANCELLERS, description: 'Who called it off once cancelled, when known')]
        public ?string $canceller,
        #[OA\Property(description: 'How long it lasts, once planned')]
        public ?int $durationInMinutes,
        public ?string $text,
        #[OA\Property(description: 'Where a meeting is held, written freely. Null for any other channel')]
        public ?string $place,
        #[OA\Property(description: 'The web address to join a video call. Null for any other channel, or until it is known')]
        public ?string $link,
        #[OA\Property(description: 'A video call without a link waits for the link that its holder\'s calendar creates. Read it again until the link comes')]
        public bool $linkAwaited,
        #[OA\Property(description: 'The number to call the interlocutor at: the contact\'s number read in clear, or the organization\'s own. None when unknown or masked')]
        public ?string $phoneNumber,
        #[OA\Property(description: 'Its text may hold personal data. It is flagged to a prospector who reads it without the right to manage personal data')]
        public bool $textFlagged,
        #[OA\Property(description: 'Once its contact is forgotten, its text may still hold their personal data, until it is declared redacted')]
        public bool $textToRedact,
        #[OA\Property(description: 'Once its contact is forgotten, its text or its files may still hold their personal data')]
        public bool $toRedact,
        #[OA\Property(description: 'The organizations it is kept for: those the contact was part of when it was logged, or the organization itself')]
        public array $organizationIds,
        public StampResource $noted,
        #[OA\Property(description: 'The last correction. Null while it is as it was logged')]
        public ?StampResource $corrected,
        #[OA\Property(description: 'Who declared it redacted, and when. Null while it is not redacted')]
        public ?StampResource $redacted,
        #[OA\Property(description: 'The files attached to it, in the order they were attached')]
        public array $attachments,
        #[OA\Property(description: 'How many attached files are hidden from a prospector who reads it without the right to manage personal data')]
        public int $hiddenAttachments,
        #[OA\Property(description: 'The campaign it was logged in. None for an exchange outside any campaign')]
        public ?CampaignOfExchangeResource $campaign,
        #[OA\Property(description: 'Its contact booked it through its holder\'s public link. The holder planned nothing themselves (ADR 0164)')]
        public bool $bookedByLink,
        #[OA\Property(enum: ExchangeResource::PROVIDERS, description: ExchangeResource::IMPORTED_FROM_DESCRIPTION)]
        public ?string $importedFrom,
        #[OA\Property(description: ExchangeResource::CREATED_BY_AN_AI_DESCRIPTION)]
        public bool $createdByAnAI,
        #[OA\Property(description: RevisionDescription::OF_A_RECORD)]
        public int $revision,
        #[SerializedName('_links')]
        #[Context([AbstractObjectNormalizer::SKIP_NULL_VALUES => true])]
        public ExchangeDetailLinksResource $links,
    ) {
    }
}
