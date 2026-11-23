<?php

declare(strict_types=1);

namespace App\Contacts\Resource;

use OpenApi\Attributes as OA;

/**
 * The organization proposed to attach a contact to (ADR 0146).
 */
final readonly class ProposedOrganizationResource
{
    public function __construct(
        #[OA\Property(description: 'The account\'s organization whose email has the same domain as the contact\'s. There is none for a public mailbox, or for a domain no organization has')]
        public ?OrganizationProposedResource $organization,
    ) {
    }
}
