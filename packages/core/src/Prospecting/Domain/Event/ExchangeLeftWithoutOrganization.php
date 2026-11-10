<?php

declare(strict_types=1);

namespace Prospecter\Core\Prospecting\Domain\Event;

/**
 * The exchange is kept for no organization of the account any more: it must not stay (ADR 0074).
 */
final readonly class ExchangeLeftWithoutOrganization extends ExchangeEvent
{
}
