<?php

declare(strict_types=1);

namespace Prospecter\Core\Prospecting\Domain\Model;

/**
 * What a prospector may do with a contact's or an organization's timeline as a whole.
 */
enum TimelineIntent
{
    /** Read the exchanges with the contact, or with the organization and its contacts. */
    case ConsultTimeline;
    /** Log a new exchange with the contact, or with the organization or one of its contacts. */
    case NoteExchange;
    /** Plan an exchange to come with the contact, or with the organization or one of its contacts. */
    case PlanExchange;
}
