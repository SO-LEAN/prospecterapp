<?php

declare(strict_types=1);

namespace Prospecter\Core\Prospecting\Domain;

/**
 * What the state of an exchange offers; who may carry it out is decided by the API (ADR 0081).
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
