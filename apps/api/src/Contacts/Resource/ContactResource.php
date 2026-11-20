<?php

namespace App\Contacts\Resource;

use App\PersonalData\Resource\PersonContactDetailsResource;
use App\PersonalData\Resource\PersonNameResource;
use OpenApi\Attributes as OA;
use Symfony\Component\Serializer\Attribute\Context;
use Symfony\Component\Serializer\Attribute\SerializedName;
use Symfony\Component\Serializer\Normalizer\AbstractObjectNormalizer;

/**
 * A person the account prospects, in clear for a member with the right to manage personal data, masked for
 * the others (ADR 0031).
 */
final readonly class ContactResource
{
    /**
     * @param list<PossibleDuplicateOfContactResource> $possibleDuplicates the contacts of the same organization they resemble, named to a reader with the right to manage personal data only
     */
    public function __construct(
        public string $id,
        public PersonNameResource $name,
        #[OA\Property(description: 'How one addresses the contact, x when unknown, read in clear like their name', enum: ['ms', 'mr', 'x'])]
        public string $civility,
        public ?string $jobTitle,
        public PersonContactDetailsResource $contactDetails,
        #[OA\Property(description: 'The data are masked, but the name of a contact: the reader has no right to read personal data in clear')]
        public bool $masked,
        #[SerializedName('_links')]
        #[Context([AbstractObjectNormalizer::SKIP_NULL_VALUES => true])]
        public ContactLinksResource $links,
        public array $possibleDuplicates,
        #[OA\Property(description: 'Until when the contact waits for the reader, who met them, to attach them to an organization (ADR 0146); absent otherwise', nullable: false)]
        #[Context([AbstractObjectNormalizer::SKIP_NULL_VALUES => true])]
        public ?ToAttachResource $toAttach = null,
    ) {
    }
}
