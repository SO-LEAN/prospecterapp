<?php

namespace App\Contacts\Resource;

use App\Shared\Resource\LinkResource;
use OpenApi\Attributes as OA;
use Prospecter\Core\Prospecting\Application\ExchangeInTimeline;

final readonly class OrganizationOfContactResource
{
    public function __construct(
        public string $id,
        public string $name,
        #[OA\Property(description: 'The organization, GET /api/organizations/{id}')]
        public LinkResource $organization,
    ) {
    }

    /**
     * The organization of the contact an exchange is kept for: none with the organization itself, or for a contact
     * no longer part of it.
     *
     * @param array<string, list<self>> $organizationsOfContacts by contact id
     */
    public static function keptFor(ExchangeInTimeline $exchange, array $organizationsOfContacts): ?self
    {
        return null === $exchange->personId ? null : array_find($organizationsOfContacts[$exchange->personId] ?? [], static fn (self $organization): bool => in_array($organization->id, $exchange->organizationIds, true));
    }
}
