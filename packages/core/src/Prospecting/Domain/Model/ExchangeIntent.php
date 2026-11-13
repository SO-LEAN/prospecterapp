<?php

declare(strict_types=1);

namespace Prospecter\Core\Prospecting\Domain\Model;

/**
 * What the state of an exchange offers; who may carry it out is decided by the API (ADR 0081), but a planned
 * exchange, handled by its holder only.
 */
enum ExchangeIntent
{
    /** Correct its channel, its direction, whether it was missed, its moment and its text, once it happened. */
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
    /** Mark done a planned exchange whose time has come, with its report. */
    case MarkExchangeDone;
    /** Mark missed a planned exchange whose time has come: the contact did not come or answer. */
    case MarkExchangeMissed;
    /** Move a planned exchange to another moment to come. */
    case MoveExchange;
    /** Cancel a planned exchange. */
    case CancelExchange;
}
