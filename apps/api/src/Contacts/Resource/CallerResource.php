<?php

namespace App\Contacts\Resource;

use App\Prospecting\Resource\OrganizationResource;

/**
 * Who has the number calling: the organizations I prospect and my contacts having it, each with the link to
 * its card; both empty when nobody is recognized.
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
