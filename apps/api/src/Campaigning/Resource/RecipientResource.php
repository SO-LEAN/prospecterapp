<?php

namespace App\Campaigning\Resource;

use App\Contacts\Resource\ContactInListResource;
use OpenApi\Attributes as OA;
use Symfony\Component\Serializer\Attribute\Context;
use Symfony\Component\Serializer\Attribute\SerializedName;
use Symfony\Component\Serializer\Normalizer\AbstractObjectNormalizer;

/**
 * A contact a frozen campaign keeps, and where the team is with them; erased by PersonalData from a started campaign,
 * kept without its name.
 */
final readonly class RecipientResource
{
    public function __construct(
        #[OA\Property(description: 'The contact, none once erased')]
        public ?ContactInListResource $contact,
        #[OA\Property(description: 'To handle, or no longer once erased', enum: ['pending', 'erased'])]
        public string $status,
        #[SerializedName('_links')]
        #[Context([AbstractObjectNormalizer::SKIP_NULL_VALUES => true])]
        public RecipientLinksResource $links,
    ) {
    }
}
