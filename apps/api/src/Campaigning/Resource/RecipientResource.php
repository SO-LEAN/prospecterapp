<?php

declare(strict_types=1);

namespace App\Campaigning\Resource;

use App\Contacts\Resource\ContactInListResource;
use OpenApi\Attributes as OA;
use Symfony\Component\Serializer\Attribute\Context;
use Symfony\Component\Serializer\Attribute\SerializedName;
use Symfony\Component\Serializer\Normalizer\AbstractObjectNormalizer;

/**
 * A contact that a frozen campaign keeps, and where the team stands with them (ADR 0145). A processed recipient records
 * whether the call went through. A dismissed one records the reason. A recipient that PersonalData erased from a
 * started campaign is kept without their name, with what the team did with them.
 */
final readonly class RecipientResource
{
    public function __construct(
        #[OA\Property(description: 'The contact, null once erased. The status is kept')]
        public ?ContactInListResource $contact,
        #[OA\Property(description: 'To handle, processed by an exchange, dismissed without one, or erased while still to handle', enum: ['pending', 'processed', 'dismissed', 'erased'])]
        public string $status,
        #[OA\Property(description: 'Processed by a call that did not go through')]
        public bool $missed,
        #[OA\Property(description: 'Why they were dismissed, when a reason was given')]
        public ?string $reason,
        #[SerializedName('_links')]
        #[Context([AbstractObjectNormalizer::SKIP_NULL_VALUES => true])]
        public RecipientLinksResource $links,
    ) {
    }
}
