<?php

declare(strict_types=1);

namespace Prospecter\Core\Prospecting\Application\Exchange\ConsultExchange;

use Prospecter\Core\Prospecting\Application\OrganizationSheet;
use Prospecter\Core\Prospecting\Application\PersonsShown;

/**
 * Who took part in an exchange, as its detail shows them (ADR 0217): its contact and the members who logged, corrected
 * and redacted it, as PersonalData lets the reader read them, or the organization itself when the exchange has no
 * contact and the organization is still known.
 */
final readonly class Participants
{
    public function __construct(
        public PersonsShown $persons,
        public ?OrganizationSheet $organization = null,
    ) {
    }
}
