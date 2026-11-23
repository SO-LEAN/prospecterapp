<?php

declare(strict_types=1);

namespace App\Contacts\Resource;

use App\Prospecting\Resource\OrganizationResource;

/**
 * Who has the calling number: the organizations I prospect and my contacts that have it. Each one comes with the
 * link to its card. Both lists are empty when nobody is recognized.
 */
final readonly class CallerResource
{
    /**
     * @param list<OrganizationResource>  $organizations
     * @param list<ContactInListResource> $contacts
     */
    public function __construct(
        public array $organizations,
        public array $contacts,
    ) {
    }
}
