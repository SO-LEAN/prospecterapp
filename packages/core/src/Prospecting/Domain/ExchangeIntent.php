<?php

declare(strict_types=1);

namespace Prospecter\Core\Prospecting\Domain;

/**
 * What a prospector may do with an exchange; reading it, correcting or erasing it needs the right to manage
 * personal data.
 */
enum ExchangeIntent
{
    /** Correct its channel, its direction, whether a call was missed, its moment and its text. */
    case CorrectExchange;
    /** Erase it from every timeline. */
    case EraseExchange;
}
