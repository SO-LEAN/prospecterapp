<?php

declare(strict_types=1);

namespace Prospecter\Core\Connectors\Domain\Model;

use Prospecter\Core\Connectors\Domain\Port\Correspondents;
use Prospecter\Core\SharedKernel\Domain\AccountId;
use Prospecter\Core\SharedKernel\Domain\Email;
use Prospecter\Core\SharedKernel\Domain\OrganizationId;
use Prospecter\Core\SharedKernel\Domain\PersonId;

/**
 * Who of the account an e-mail was exchanged with: a contact, or else an organization itself, by its address
 * (ADR 0154).
 */
final readonly class Correspondent
{
    private function __construct(
        public ?PersonId $contact,
        public ?OrganizationId $organization,
    ) {
    }

    /**
     * A contact having the address is the correspondent, even when an organization has it too: the exchange is kept
     * for the organizations of the contact.
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
