<?php

declare(strict_types=1);

namespace Prospecter\Core\Prospecting\Domain\Model;

use Prospecter\Core\SharedKernel\Domain\PersonId;

/**
 * A contact. The exchange or the to-do is kept for the organizations the contact is part of.
 */
final readonly class ContactCorrespondent extends Correspondent
{
    /**
     * @param list<OrganizationId> $organizationsOfTheContact
     */
    public function __construct(PersonId $contact, array $organizationsOfTheContact)
    {
        parent::__construct($contact, $contact, $organizationsOfTheContact);
    }
}
