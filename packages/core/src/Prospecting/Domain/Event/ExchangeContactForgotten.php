<?php

declare(strict_types=1);

namespace Prospecter\Core\Prospecting\Domain\Event;

/**
 * The contact of an exchange was forgotten: the exchange stays, to make conform.
 */
final readonly class ExchangeContactForgotten extends ExchangeEvent
{
}
