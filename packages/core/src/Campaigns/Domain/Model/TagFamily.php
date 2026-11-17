<?php

declare(strict_types=1);

namespace Prospecter\Core\Campaigns\Domain\Model;

/**
 * What the predefined tags say: the type of an organization, the relation with a contact, the technologies.
 */
enum TagFamily: string
{
    case OrganizationType = 'organizationType';
    case Relationship = 'relationship';
    case Technology = 'technology';
}
