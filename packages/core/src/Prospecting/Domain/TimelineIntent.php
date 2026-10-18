<?php

declare(strict_types=1);

namespace Prospecter\Core\Prospecting\Domain;

/**
 * What a prospector may do with the timeline of a contact, as a whole.
 */
enum TimelineIntent
{
    /** Note a new exchange with the contact; needs the right to manage personal data. */
    case NoteExchange;
}
