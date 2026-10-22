<?php

declare(strict_types=1);

namespace Prospecter\Core\Prospecting\Domain;

/**
 * What a prospector of the account may do with an exchange: retouching its text, to make it conform, is open to
 * every one of them; the rest needs the right to manage personal data.
 */
enum ExchangeIntent
{
    /** Correct its channel, its direction, whether a call was missed, its moment and its text. */
    case CorrectExchange;
    /** Correct its text only. */
    case CorrectText;
    /** Erase it from every timeline. */
    case EraseExchange;
    /** Join a file to it, while it has room for one. */
    case AttachFile;
    /** Remove one of the files joined to it. */
    case RemoveAttachment;
}
