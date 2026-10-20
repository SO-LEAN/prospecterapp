<?php

namespace App\Contacts\Resource;

use OpenApi\Attributes as OA;

/**
 * Who communicated in an exchange of the timeline of an organization: one of its contacts, or the organization
 * itself.
 */
final readonly class InterlocutorResource
{
    public const string ORGANIZATION = 'organization';
    public const string PERSON = 'person';

    public function __construct(
        #[OA\Property(enum: [self::ORGANIZATION, self::PERSON])]
        public string $kind,
        public string $id,
        #[OA\Property(description: 'Its name; none for a person no longer known')]
        public ?string $name,
    ) {
    }
}
