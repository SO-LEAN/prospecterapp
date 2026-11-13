<?php

declare(strict_types=1);

namespace Prospecter\Core\Prospecting\Domain\Model;

/**
 * What the state of a to-do offers; who may carry it out is decided by the API (ADR 0081).
 */
enum ToDoIntent
{
    /** Change its text and when it is due. */
    case ReviseToDo;
    /** Tick it off, once done. */
    case TickOffToDo;
    /** Untick it, ticked off by mistake. */
    case UntickToDo;
    /** Erase it. */
    case EraseToDo;
}
