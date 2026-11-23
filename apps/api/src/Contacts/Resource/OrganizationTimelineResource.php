<?php

declare(strict_types=1);

namespace App\Contacts\Resource;

use App\Prospecting\Resource\TimelineLinksResource;
use OpenApi\Attributes as OA;
use Symfony\Component\Serializer\Attribute\Context;
use Symfony\Component\Serializer\Attribute\SerializedName;
use Symfony\Component\Serializer\Normalizer\AbstractObjectNormalizer;

/**
 * A slice of the exchanges with an organization and with its contacts, the most recent moment first (ADR 0049,
 * 0076). The link next leads to the next slice.
 */
final readonly class OrganizationTimelineResource
{
    /**
     * @param list<OrganizationExchangeResource> $items
     */
    public function __construct(
        public array $items,
        #[OA\Property(description: 'How many of the organization\'s exchanges are to redact because their contact was forgotten')]
        public int $toRedact,
        #[SerializedName('_links')]
        #[Context([AbstractObjectNormalizer::SKIP_NULL_VALUES => true])]
        public TimelineLinksResource $links,
    ) {
    }
}
