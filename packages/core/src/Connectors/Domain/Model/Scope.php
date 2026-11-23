<?php

declare(strict_types=1);

namespace Prospecter\Core\Connectors\Domain\Model;

/**
 * What Prospecter asks a provider to grant. Every connection asks for the member's calendar « Prospecter ». The
 * e-mail import also asks to read their mailbox (ADR 0154).
 */
enum Scope: string
{
    case Calendar = 'calendar';
    case Mailbox = 'mailbox';
}
