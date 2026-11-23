<?php

declare(strict_types=1);

namespace Prospecter\Core\Campaigns\Domain\Rule;

use Prospecter\Core\Campaigns\Domain\Model\RecipientStatus;

/**
 * How far a campaign has gone (ADR 0144, 0145). It counts the recipients that an exchange processed and those
 * dismissed. Before the campaign starts, it counts nobody. It counts the recipients processed today on the member's
 * clock, for the daily cap.
 */
final class CampaignProgress
{
    /**
     * @param list<RecipientStatus> $statuses its recipients' statuses
     */
    #[\NoDiscard]
    public static function processedAmong(array $statuses): int
    {
        return self::counted(RecipientStatus::Processed, $statuses);
    }

    /**
     * @param list<RecipientStatus> $statuses its recipients' statuses
     */
    #[\NoDiscard]
    public static function dismissedAmong(array $statuses): int
    {
        return self::counted(RecipientStatus::Dismissed, $statuses);
    }

    /**
     * @param list<?\DateTimeImmutable> $processedAt when each recipient was processed, null for one not processed
     */
    #[\NoDiscard]
    public static function processedOnTheDayOf(\DateTimeImmutable $at, \DateTimeZone $clock, array $processedAt): int
    {
        $today = self::dayOf($at, $clock);

        return count(array_filter($processedAt, static fn (?\DateTimeImmutable $moment): bool => null !== $moment && $today === self::dayOf($moment, $clock)));
    }

    private static function dayOf(\DateTimeImmutable $moment, \DateTimeZone $clock): string
    {
        return $moment->setTimezone($clock)->format('Y-m-d');
    }

    /**
     * @param list<RecipientStatus> $statuses
     */
    private static function counted(RecipientStatus $status, array $statuses): int
    {
        return count(array_filter($statuses, static fn (RecipientStatus $each): bool => $status === $each));
    }
}
