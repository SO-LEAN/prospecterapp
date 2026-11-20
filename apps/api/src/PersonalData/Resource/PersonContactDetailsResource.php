<?php

namespace App\PersonalData\Resource;

use App\Shared\Resource\ExternalLinkResource;
use OpenApi\Attributes as OA;
use Prospecter\Core\PersonalData\Application\PersonCard;

/**
 * The contact details of a person: an email, a phone number, a language, and their links to the tools outside.
 */
final readonly class PersonContactDetailsResource
{
    public function __construct(
        public ?string $email,
        #[OA\Property(description: 'In the international format', example: '+33 3 88 11 22 33')]
        public ?string $phoneNumber,
        public ?string $language,
        /** @var list<ExternalLinkResource> one link of stars when masked, whatever their number */
        public array $links,
    ) {
    }

    public static function of(PersonCard $person): self
    {
        return new self($person->email, $person->phoneNumber, $person->language, ExternalLinkResource::allOf($person->links));
    }
}
