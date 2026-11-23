<?php

declare(strict_types=1);

namespace Prospecter\Core\Prospecting\Domain\Model;

/**
 * What an exchange's state offers. The API decides who may carry it out (ADR 0081). The exception is a planned
 * exchange: only its holder handles it.
 */
enum ExchangeIntent
{
    /** Correct its channel, its direction, whether it was missed, its moment and its text, once it has happened. */
    case CorrectExchange;
    /** Correct its text only. */
    case CorrectText;
    /** Correct where a meeting is held, or a video call's link, planned or done. */
    case CorrectVenue;
    /** Declare an exchange's text redacted once its contact was forgotten, while it is not redacted yet. */
    case MakeConform;
    /** Erase it from every timeline. */
    case EraseExchange;
    /** Attach a file to it, while it has room for one. */
    case AttachFile;
    /** Remove one of its attached files. */
    case RemoveAttachment;
    /** Mark done a planned exchange whose time has come, with its report. */
    case MarkExchangeDone;
    /** Mark missed a planned exchange whose time has come: the contact did not come or answer. */
    case MarkExchangeMissed;
    /** Move a planned exchange to another moment to come. */
    case MoveExchange;
    /** Put a planned exchange back at its former moment right after a move, while that moment is still to come. */
    case MoveExchangeBack;
    /** Cancel a planned exchange. */
    case CancelExchange;
}
