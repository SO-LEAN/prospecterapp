<?php

namespace App\Consistency;

/**
 * The command correcting a discrepancy: the figures are counted again; a reaction missed is given again to the worker
 * from the queue of its failures, or, when no failure keeps it, published again from the outbox of its commit; an item
 * missing from its list gets its keys written again.
 */
enum Correction: string
{
    case RebuildTheFigures = 'app:reporting:rebuild';
    case GiveTheReactionAgain = 'app:integration:redrive, or app:outbox:republish --since=<moment of the gesture> when no failure keeps it';
    case RedriveTheFailures = 'app:integration:redrive';
    case RepublishTheOutbox = 'app:outbox:republish --since=<moment of the first failure>';
    case WriteTheKeysAgain = 'app:keys:rebuild';
}
