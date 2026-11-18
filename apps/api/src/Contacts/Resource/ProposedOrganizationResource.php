<?php

namespace App\Contacts\Resource;

use OpenApi\Attributes as OA;

/**
 * The organization proposed to attach a contact to (ADR 0146).
 */
final readonly class ProposedOrganizationResource
{
    public function __construct(
        #[OA\Property(description: 'The organization of the account whose email has the domain of that of the contact; none for a public mailbox, or a domain none has')]
        public ?OrganizationProposedResource $organization,
    ) {
    }
}
