<?php

declare(strict_types=1);

namespace Prospecter\Core\Connectors\Domain\Model;

/**
 * Where the push of an exchange into the calendar of its holder stands (ADR 0133): pending until a push reaches the
 * calendar, synced once one did, failed once the provider refused it for good.
 */
enum SyncState: string
{
    case Pending = 'pending';
    case Synced = 'synced';
    case Failed = 'failed';
}
