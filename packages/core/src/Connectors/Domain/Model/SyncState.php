<?php

declare(strict_types=1);

namespace Prospecter\Core\Connectors\Domain\Model;

/**
 * The state of an exchange's push into its holder's calendar (ADR 0133). It is pending until a push reaches the
 * calendar. It is synced once a push did. It is failed once the provider refused the push for good.
 */
enum SyncState: string
{
    case Pending = 'pending';
    case Synced = 'synced';
    case Failed = 'failed';
}
