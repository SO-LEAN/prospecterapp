<?php

namespace App\Consistency;

/**
 * The command correcting a discrepancy: the figures are counted again; the facts the tags of the contacts of an
 * account derive from are recorded again; a reaction missed is given again to the worker from the queue of its
 * failures, or, when no failure keeps it, published again from the outbox of its commit; an item missing from its list
 * gets its keys written again; an exchange its calendar lacks is pushed again by its holder; a contact left unattached
 * past their day is erased; two contacts noting the same link are merged or corrected by the members.
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
