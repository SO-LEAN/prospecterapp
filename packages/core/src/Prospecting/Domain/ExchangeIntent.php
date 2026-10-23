<?php

declare(strict_types=1);

namespace Prospecter\Core\Prospecting\Domain;

/**
 * What a prospector of the account may do with an exchange: retouching its text, and declaring it conform, are
 * open to every one of them; the rest needs the right to manage personal data.
 */
enum ExchangeIntent
{
    /** Correct its channel, its direction, whether a call was missed, its moment and its text. */
    case CorrectExchange;
    /** Correct its text only. */
    case CorrectText;
    /** Declare conform the text of an exchange whose contact was forgotten, while it is not yet. */
    case MakeConform;
    /** Erase it from every timeline. */
    case EraseExchange;
    /** Join a file to it, while it has room for one. */
    case AttachFile;
    /** Remove one of the files joined to it. */
    case RemoveAttachment;
}
