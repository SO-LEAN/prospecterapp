<?php

declare(strict_types=1);

namespace Prospecter\Core\Prospecting\Domain\Model;

/**
 * What a to-do's state offers. The API decides who may carry it out (ADR 0081).
 */
enum ToDoIntent
{
    /** Change its text and when it is due. */
    case ReviseToDo;
    /** Tick it off once it is done. */
    case TickOffToDo;
    /** Untick it after it was ticked off by mistake. */
    case UntickToDo;
    /** Erase it. */
    case EraseToDo;
}
