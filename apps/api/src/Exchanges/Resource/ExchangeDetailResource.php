<?php

namespace App\Exchanges\Resource;

use App\Prospecting\Input\ExchangeInput;
use OpenApi\Attributes as OA;
use Symfony\Component\Serializer\Attribute\Context;
use Symfony\Component\Serializer\Attribute\SerializedName;
use Symfony\Component\Serializer\Normalizer\AbstractObjectNormalizer;

/**
 * The whole of an exchange with a contact: who noted it, and who corrected it last.
 */
final readonly class ExchangeDetailResource
{
    /**
     * @param list<string> $organizationIds
     */
    public function __construct(
        public string $id,
        public ExchangeContactResource $contact,
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
        public StampResource $noted,
        #[OA\Property(description: 'The last correction, null while it is as it was noted')]
        public ?StampResource $corrected,
        #[SerializedName('_links')]
        #[Context([AbstractObjectNormalizer::SKIP_NULL_VALUES => true])]
        public ExchangeDetailLinksResource $links,
    ) {
    }
}
