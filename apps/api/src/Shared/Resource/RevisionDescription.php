<?php

declare(strict_types=1);

namespace App\Shared\Resource;

/**
 * How the API documents a record's `revision`, the same on every record and every change (ADR 0187).
 */
final class RevisionDescription
{
    public const string OF_A_RECORD = 'The record\'s revision as read. Send it back with a change to what was read: if the record changed since, the change is refused (409 changedMeanwhile).';
    public const string OF_A_CHANGE = 'The record\'s revision as the client read it. If the record changed since, the change is refused (409 changedMeanwhile) instead of erasing the other change. Without it, the change holds to the record as the API reads it.';
}
