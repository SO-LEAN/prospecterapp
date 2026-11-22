<?php

declare(strict_types=1);

namespace Prospecter\Core\Campaigns\Domain\Policy;

use Prospecter\Core\Campaigns\Domain\Model\RecipientStatus;

/**
 * How far a campaign went (ADR 0144, 0145): its recipients processed by an exchange, and those dismissed; nobody before
 * it is started. Its recipients processed today are counted on the member's clock, for its daily cap.
 */
final class CampaignProgress
{
    /**
     * @param list<RecipientStatus> $statuses of its recipients
     */
    #[\NoDiscard]
    public static function processedAmong(array $statuses): int
    {
        return self::counted(RecipientStatus::Processed, $statuses);
    }

    /**
     * @param list<RecipientStatus> $statuses of its recipients
     */
    #[\NoDiscard]
    public static function dismissedAmong(array $statuses): int
    {
        return self::counted(RecipientStatus::Dismissed, $statuses);
    }

    /**
     * @param list<?\DateTimeImmutable> $processedAt the moment each of its recipients was processed, none for one not processed
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
