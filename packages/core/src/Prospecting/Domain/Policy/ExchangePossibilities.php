<?php

declare(strict_types=1);

namespace Prospecter\Core\Prospecting\Domain\Policy;

use Prospecter\Core\Prospecting\Domain\Model\Exchange;
use Prospecter\Core\Prospecting\Domain\Model\ExchangeIntent;
use Prospecter\Core\Prospecting\Domain\Model\ExchangeSituation;
use Prospecter\Core\Prospecting\Domain\Model\ExchangeStatus;

/**
 * What the state of an exchange offers now (ADR 0088): the exchange refuses the rest, the reading of an exchange
 * tells it. A planned exchange is handled by its holder: moved or cancelled while it is to come, marked done or
 * missed once its time has come.
 */
final class ExchangePossibilities
{
    /**
     * @return list<ExchangeIntent>
     */
    #[\NoDiscard]
    public static function given(ExchangeSituation $situation): array
    {
        $handled = ExchangeStatus::Planned === $situation->status && $situation->heldByTheMember;

        return array_values(array_filter(ExchangeIntent::cases(), static fn (ExchangeIntent $intent): bool => match ($intent) {
            ExchangeIntent::CorrectExchange => self::happened($situation->status),
            ExchangeIntent::CorrectVenue => $situation->channel->hasAVenue(),
            ExchangeIntent::AttachFile => $situation->attachments < Exchange::MOST_ATTACHMENTS,
            ExchangeIntent::MakeConform => $situation->awaitsBeingMadeConform,
            ExchangeIntent::MarkExchangeDone, ExchangeIntent::MarkExchangeMissed => $handled && $situation->due,
            ExchangeIntent::MoveExchange, ExchangeIntent::CancelExchange => $handled,
            default => true,
        }));
    }

    private static function happened(ExchangeStatus $status): bool
    {
        return in_array($status, [ExchangeStatus::Done, ExchangeStatus::Missed], true);
    }
}
