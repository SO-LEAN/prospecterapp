<?php

declare(strict_types=1);

namespace Prospecter\Core\Campaigns\Domain\Model;

/**
 * Where the team is with a recipient of a campaign: to handle, once frozen; no longer, once PersonalData erased the
 * contact of a started campaign, kept without its name.
 */
enum RecipientStatus: string
{
    case Pending = 'pending';
    case Erased = 'erased';
}
