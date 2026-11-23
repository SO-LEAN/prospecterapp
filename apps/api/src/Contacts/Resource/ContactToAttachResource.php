<?php

declare(strict_types=1);

namespace App\Contacts\Resource;

use App\PersonalData\Resource\PersonNameResource;
use OpenApi\Attributes as OA;
use Symfony\Component\Serializer\Attribute\Context;
use Symfony\Component\Serializer\Attribute\SerializedName;
use Symfony\Component\Serializer\Normalizer\AbstractObjectNormalizer;

/**
 * A contact the signed-in member met, who waits for the member to attach them to an organization (ADR 0146).
 */
final readonly class ContactToAttachResource
{
    public function __construct(
        public string $id,
        public PersonNameResource $name,
        #[OA\Property(description: 'Their email as the member reads it, masked without the right to manage personal data')]
        public ?string $email,
        #[OA\Property(description: 'Their last meeting with the member, past or to come')]
        public \DateTimeImmutable $lastMeetingAt,
        #[OA\Property(description: 'The moment from which they are erased if nobody attaches them: the start of the 30th day after their last meeting, on that meeting\'s clock')]
        public \DateTimeImmutable $erasableFrom,
        #[SerializedName('_links')]
        #[Context([AbstractObjectNormalizer::SKIP_NULL_VALUES => true])]
        public ContactToAttachLinksResource $links,
    ) {
    }
}
