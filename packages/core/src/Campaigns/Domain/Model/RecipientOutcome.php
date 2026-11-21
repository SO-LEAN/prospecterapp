<?php

declare(strict_types=1);

namespace Prospecter\Core\Campaigns\Domain\Model;

/**
 * How the exchange that processed a recipient went (ADR 0145): done, or missed when the call did not go through.
 */
enum RecipientOutcome
{
    case Done;
    case Missed;
}
