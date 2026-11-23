<?php

declare(strict_types=1);

namespace Prospecter\Core\Campaigns\Domain\Model;

use Prospecter\Core\SharedKernel\Domain\PersonId;
use Prospecter\Core\SharedKernel\Domain\Uuid;

/** What carries tags: an organization or a contact, by its id in the context that keeps it. */
final readonly class Holder
{
    private function __construct(public HolderKind $kind, public Uuid $id)
    {
    }

    public static function organization(OrganizationId $organization): self
    {
        return new self(HolderKind::Organization, $organization);
    }

    public static function contact(PersonId $contact): self
    {
        return new self(HolderKind::Contact, $contact);
    }

    public static function of(string $kind, string $id): self
    {
        return match (HolderKind::from($kind)) {
            HolderKind::Organization => self::organization(new OrganizationId($id)),
            HolderKind::Contact => self::contact(new PersonId($id)),
        };
    }
}
