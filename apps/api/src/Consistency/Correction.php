<?php

declare(strict_types=1);

namespace App\Consistency;

/**
 * The command that corrects a discrepancy:
 *
 * - The figures are counted again.
 * - An account's contact tags derive from facts, and those facts are recorded again.
 * - A missed reaction is given to the worker again from its failure queue. When no failure keeps it, it is published
 *   again from its commit's outbox.
 * - An item missing from its list gets its keys written again.
 * - An exchange missing from its calendar is pushed again by its holder.
 * - A contact left unattached past their day is erased.
 * - The members merge or correct two contacts that hold the same link.
 */
enum Correction: string
{
    case RebuildTheFigures = 'app:reporting:rebuild';
    case RebuildTheTags = 'app:tags:rebuild --account=<account>';
    case GiveTheReactionAgain = 'app:integration:redrive, or app:outbox:republish --since=<moment of the gesture> when no failure keeps it';
    case RedriveTheFailures = 'app:integration:redrive';
    case RepublishTheOutbox = 'app:outbox:republish --since=<moment of the first failure>';
    case WriteTheKeysAgain = 'app:keys:rebuild';
    case EraseTheUnattached = 'app:contacts:erase-unattached';
    case PushTheExchangeAgain = 'its holder pushes it again from Prospecter once failed, reconnects, or changes it';
    case MergeOrCorrectByHand = 'the members merge them or correct one of them';
}
