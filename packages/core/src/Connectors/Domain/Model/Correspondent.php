<?php

declare(strict_types=1);

namespace Prospecter\Core\Connectors\Domain\Model;

use Prospecter\Core\Connectors\Domain\Port\Correspondents;
use Prospecter\Core\SharedKernel\Domain\AccountId;
use Prospecter\Core\SharedKernel\Domain\Email;
use Prospecter\Core\SharedKernel\Domain\OrganizationId;
use Prospecter\Core\SharedKernel\Domain\PersonId;

/**
 * The party in the account that an e-mail was exchanged with. Its address finds it. It is a contact, or else an
 * organization itself (ADR 0154).
 */
final readonly class Correspondent
{
    private function __construct(
        public ?PersonId $contact,
        public ?OrganizationId $organization,
    ) {
    }

    /**
     * A contact with the address is the correspondent, even when an organization has the address too. The exchange is
     * then kept for the contact's organizations.
     */
    public static function havingTheAddress(Email $address, AccountId $accountId, Correspondents $correspondents): ?self
    {
        $contact = $correspondents->contactWithEmail($accountId, $address);
        if (null !== $contact) {
            return new self($contact, null);
        }

        $organization = $correspondents->organizationWithEmail($accountId, $address);

        return null === $organization ? null : new self(null, $organization);
    }
}
