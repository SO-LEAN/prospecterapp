<?php

declare(strict_types=1);

namespace Prospecter\Core\Prospecting\Domain\Model;

/**
 * What a prospector may do with the timeline of a contact or of an organization, as a whole.
 */
enum TimelineIntent
{
    /** Read the exchanges with the contact, or with the organization and its contacts. */
    case ConsultTimeline;
    /** Note a new exchange with the contact, or with the organization or one of its contacts. */
    case NoteExchange;
}
