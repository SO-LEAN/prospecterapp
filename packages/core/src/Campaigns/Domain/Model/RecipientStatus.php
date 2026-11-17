<?php

declare(strict_types=1);

namespace Prospecter\Core\Campaigns\Domain\Model;

/**
 * Where the team is with a recipient of a campaign: to handle, once frozen.
 */
enum RecipientStatus: string
{
    case Pending = 'pending';
}
