<?php

declare(strict_types=1);

namespace App\Contacts\Resource;

use App\PersonalData\Resource\PersonContactDetailsResource;
use App\PersonalData\Resource\PersonNameResource;
use App\Prospecting\Resource\ExchangeResource;
use App\Shared\Resource\RevisionDescription;
use OpenApi\Attributes as OA;
use Symfony\Component\Serializer\Attribute\Context;
use Symfony\Component\Serializer\Attribute\SerializedName;
use Symfony\Component\Serializer\Normalizer\AbstractObjectNormalizer;

/**
 * A person the account prospects. A member with the right to manage personal data reads them unmasked. The others
 * read them masked (ADR 0031).
 */
final readonly class ContactResource
{
    /**
     * @param list<PossibleDuplicateOfContactResource> $possibleDuplicates the contacts they resemble in the same organization, named only to a reader with the right to manage personal data
     */
    public function __construct(
        public string $id,
        public PersonNameResource $name,
        #[OA\Property(description: 'How to address the contact, x when unknown. It is read unmasked, like their name', enum: ['ms', 'mr', 'x'])]
        public string $title,
        public ?string $jobTitle,
        public PersonContactDetailsResource $contactDetails,
        #[OA\Property(description: 'The data are masked, except the contact\'s name, because the reader has no right to read personal data unmasked')]
        public bool $masked,
        #[SerializedName('_links')]
        #[Context([AbstractObjectNormalizer::SKIP_NULL_VALUES => true])]
        public ContactLinksResource $links,
        public array $possibleDuplicates,
        #[OA\Property(description: ExchangeResource::CREATED_BY_AN_AI_DESCRIPTION)]
        public bool $createdByAnAI,
        #[OA\Property(description: RevisionDescription::OF_A_RECORD)]
        public int $revision,
        #[OA\Property(description: 'Until when the contact waits for the reader who met them to attach them to an organization (ADR 0146). Absent otherwise', nullable: false)]
        #[Context([AbstractObjectNormalizer::SKIP_NULL_VALUES => true])]
        public ?ToAttachResource $toAttach = null,
    ) {
    }
}
