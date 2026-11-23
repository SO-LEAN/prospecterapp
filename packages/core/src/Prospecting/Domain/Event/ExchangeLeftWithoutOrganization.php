<?php

declare(strict_types=1);

namespace Prospecter\Core\Prospecting\Domain\Event;

/**
 * The exchange is no longer kept for any organization of the account. It must not stay (ADR 0074).
 */
final readonly class ExchangeLeftWithoutOrganization extends ExchangeEvent
{
}
