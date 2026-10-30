<?php

declare(strict_types=1);

namespace Prospecter\Core\Prospecting\Domain\Policy;

use Prospecter\Core\Prospecting\Domain\Model\Exchange;
use Prospecter\Core\Prospecting\Domain\Model\ExchangeIntent;

/**
 * What the state of an exchange offers now (ADR 0088): the exchange refuses the rest, the reading of an exchange
 * tells it.
 */
final class ExchangePossibilities
{
    /**
     * @return list<ExchangeIntent>
     */
    public static function given(int $attachments, bool $awaitsBeingMadeConform): array
    {
        return array_values(array_filter(ExchangeIntent::cases(), static fn (ExchangeIntent $intent): bool => match ($intent) {
            ExchangeIntent::AttachFile => $attachments < Exchange::MOST_ATTACHMENTS,
            ExchangeIntent::MakeConform => $awaitsBeingMadeConform,
            default => true,
        }));
    }
}
