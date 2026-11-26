<?php

declare(strict_types=1);

namespace App\Contacts\Resource;

use App\Shared\Links;
use App\Shared\Resource\LinkResource;
use OpenApi\Attributes as OA;
use Prospecter\Core\Prospecting\Application\Contact\ConsultOrganizationsOfContacts\OrganizationOfContact;
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
     * Gives the organization of the contact an exchange is kept for. An exchange with the organization itself has
     * none, and so does a contact who is no longer part of it.
     *
     * @param array<string, list<OrganizationOfContact>> $organizationsOfContacts by contact id
     */
    public static function keptFor(ExchangeInTimeline $exchange, array $organizationsOfContacts, Links $links): ?self
    {
        $kept = null === $exchange->personId ? null : array_find($organizationsOfContacts[$exchange->personId] ?? [], static fn (OrganizationOfContact $organization): bool => in_array($organization->id, $exchange->organizationIds, true));

        return null === $kept ? null : self::of($kept, $links);
    }

    public static function of(OrganizationOfContact $organization, Links $links): self
    {
        return new self($organization->id, $organization->fullName, $links->to('api_organizations_show', ['id' => $organization->id]));
    }
}
