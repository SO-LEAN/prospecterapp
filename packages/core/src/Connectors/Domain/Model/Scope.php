<?php

declare(strict_types=1);

namespace Prospecter\Core\Connectors\Domain\Model;

/**
 * What Prospecter asks a provider to grant: the calendar « Prospecter » of the member, for every connection; the
 * reading of their mailbox, for the import of their e-mails (ADR 0154).
 */
enum Scope: string
{
    case Calendar = 'calendar';
    case Mailbox = 'mailbox';
}
