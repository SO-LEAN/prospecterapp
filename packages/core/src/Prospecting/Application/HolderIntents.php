<?php

declare(strict_types=1);

namespace Prospecter\Core\Prospecting\Application;

use Prospecter\Core\Prospecting\Domain\Model\Channel;
use Prospecter\Core\Prospecting\Domain\Model\ExchangeIntent;
use Prospecter\Core\Prospecting\Domain\Model\ExchangeSituation;
use Prospecter\Core\Prospecting\Domain\Model\ExchangeStatus;
use Prospecter\Core\Prospecting\Domain\Policy\ExchangePossibilities;

/**
 * What the exchanges a prospector holds offer them at a moment: what the state of each offers its holder.
 */
final class HolderIntents
{
    /**
     * @param list<ExchangeInTimeline> $exchanges held by the prospector
     *
     * @return list<string> the ids of those offering the intent
     */
    public static function offering(ExchangeIntent $intent, array $exchanges, \DateTimeImmutable $at): array
    {
        $offering = array_filter($exchanges, static fn (ExchangeInTimeline $exchange): bool => in_array($intent, ExchangePossibilities::given(self::situationOf($exchange, $at)), true));

        return array_values(array_map(static fn (ExchangeInTimeline $exchange): string => $exchange->id, $offering));
    }

    private static function situationOf(ExchangeInTimeline $exchange, \DateTimeImmutable $at): ExchangeSituation
    {
        return new ExchangeSituation($exchange->attachmentCount, $exchange->toMakeConform, ExchangeStatus::from($exchange->status), $exchange->occurredAt <= $at, heldByTheMember: true, channel: Channel::from($exchange->channel));
    }
}
