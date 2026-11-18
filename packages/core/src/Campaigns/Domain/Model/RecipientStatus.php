<?php

declare(strict_types=1);

namespace Prospecter\Core\Campaigns\Domain\Model;

/**
 * Where the team is with a recipient of a campaign (ADR 0144, 0145): to handle, once frozen; processed, by an exchange
 * noted for them in the campaign; dismissed, without an exchange; erased, once PersonalData erased the contact of a
 * started campaign still to handle, kept without its name; a recipient processed or dismissed keeps its status once
 * erased (`Recipient::$erased`).
 */
enum RecipientStatus: string
{
    case Pending = 'pending';
    case Processed = 'processed';
    case Dismissed = 'dismissed';
    case Erased = 'erased';
}
