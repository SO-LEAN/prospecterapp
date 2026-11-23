<?php

declare(strict_types=1);

namespace Prospecter\Core\Campaigns\Domain\Model;

/**
 * Where the team stands with a campaign's recipient (ADR 0144, 0145):
 *
 * - pending: to handle, once the campaign is frozen;
 * - processed: by an exchange logged for them in the campaign;
 * - dismissed: without an exchange;
 * - erased: PersonalData erased the contact from a started campaign while they were still to handle. The recipient is
 *   kept without their name.
 *
 * A processed or dismissed recipient keeps its status once erased (`Recipient::$erased`).
 */
enum RecipientStatus: string
{
    case Pending = 'pending';
    case Processed = 'processed';
    case Dismissed = 'dismissed';
    case Erased = 'erased';
}
