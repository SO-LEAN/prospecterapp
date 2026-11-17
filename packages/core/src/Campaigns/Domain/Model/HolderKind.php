<?php

declare(strict_types=1);

namespace Prospecter\Core\Campaigns\Domain\Model;

/**
 * What a member tags: an organization their account prospects, or a contact of their account.
 */
enum HolderKind: string
{
    case Organization = 'organization';
    case Contact = 'contact';
}
