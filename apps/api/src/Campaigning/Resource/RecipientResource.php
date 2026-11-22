<?php

declare(strict_types=1);

namespace App\Campaigning\Resource;

use App\Contacts\Resource\ContactInListResource;
use OpenApi\Attributes as OA;
use Symfony\Component\Serializer\Attribute\Context;
use Symfony\Component\Serializer\Attribute\SerializedName;
use Symfony\Component\Serializer\Normalizer\AbstractObjectNormalizer;

/**
 * A contact a frozen campaign keeps, and where the team is with them (ADR 0145): processed by an exchange, whether the
 * call did not go through; dismissed, why; erased by PersonalData from a started campaign, kept without its name,
 * with what the team did with them.
 */
final readonly class RecipientResource
{
    public function __construct(
        #[OA\Property(description: 'The contact, none once erased, its status kept')]
        public ?ContactInListResource $contact,
        #[OA\Property(description: 'To handle, processed by an exchange, dismissed without one, or no longer once erased while to handle', enum: ['pending', 'processed', 'dismissed', 'erased'])]
        public string $status,
        #[OA\Property(description: 'Processed by a call that did not go through')]
        public bool $missed,
        #[OA\Property(description: 'Why they were dismissed, when said')]
        public ?string $reason,
        #[SerializedName('_links')]
        #[Context([AbstractObjectNormalizer::SKIP_NULL_VALUES => true])]
        public RecipientLinksResource $links,
    ) {
    }
}
