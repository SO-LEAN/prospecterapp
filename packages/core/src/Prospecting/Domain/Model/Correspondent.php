<?php

declare(strict_types=1);

namespace Prospecter\Core\Prospecting\Domain\Model;

use Prospecter\Core\SharedKernel\Domain\OrganizationId;
use Prospecter\Core\SharedKernel\Domain\PersonId;

/**
 * Who an exchange is with, and the organizations that keep it: a contact, kept for their organizations, or the
 * organization itself (its switchboard, its generic e-mail), kept for itself alone.
 */
final readonly class Correspondent
{
    /**
     * @param list<OrganizationId> $keptFor
     */
    private function __construct(
        public PersonId|OrganizationId $identity,
        public ?PersonId $contact,
        public array $keptFor,
    ) {
    }

    /**
     * @param list<OrganizationId> $organizationsOfTheContact
     */
    public static function contact(PersonId $contact, array $organizationsOfTheContact): self
    {
        return new self($contact, $contact, $organizationsOfTheContact);
    }

    public static function organization(OrganizationId $organization): self
    {
        return new self($organization, null, [$organization]);
    }
}
