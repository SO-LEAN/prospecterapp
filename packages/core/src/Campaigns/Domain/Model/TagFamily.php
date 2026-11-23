<?php

declare(strict_types=1);

namespace Prospecter\Core\Campaigns\Domain\Model;

/** What the predefined tags describe: an organization's type, the relationship with a contact, and technologies. */
enum TagFamily: string
{
    case OrganizationType = 'organizationType';
    case Relationship = 'relationship';
    case Technology = 'technology';
}
