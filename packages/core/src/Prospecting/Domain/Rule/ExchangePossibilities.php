<?php

declare(strict_types=1);

namespace Prospecter\Core\Prospecting\Domain\Rule;

use Prospecter\Core\Prospecting\Domain\Model\Exchange;
use Prospecter\Core\Prospecting\Domain\Model\ExchangeIntent;
use Prospecter\Core\Prospecting\Domain\Model\ExchangeSituation;
use Prospecter\Core\Prospecting\Domain\Model\ExchangeStatus;

/**
 * What an exchange's state offers now (ADR 0088). The exchange refuses the rest, and the exchange's reading tells it.
 * Only its holder handles a planned exchange. The holder moves or cancels it while it is to come, and marks it done or
 * missed once its time has come. The holder moves a just moved exchange back while its former moment is still to come.
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
            ExchangeIntent::MoveExchangeBack => $handled && $situation->formerMomentToCome,
            default => true,
        }));
    }

    private static function happened(ExchangeStatus $status): bool
    {
        return in_array($status, [ExchangeStatus::Done, ExchangeStatus::Missed], true);
    }
}
