<?php

declare(strict_types=1);

namespace Prospecter\Core\Campaigns\Domain\Policy;

use Prospecter\Core\Campaigns\Domain\Model\RecipientStatus;

/**
 * How far a campaign went (ADR 0144, 0145): its recipients processed by an exchange, and those dismissed; nobody before
 * it is started.
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
     * @param list<RecipientStatus> $statuses
     */
    private static function counted(RecipientStatus $status, array $statuses): int
    {
        return count(array_filter($statuses, static fn (RecipientStatus $each): bool => $status === $each));
    }
}
