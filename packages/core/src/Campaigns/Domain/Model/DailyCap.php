<?php

declare(strict_types=1);

namespace Prospecter\Core\Campaigns\Domain\Model;

use Prospecter\Core\Campaigns\Domain\Exception\InvalidDailyCap;

/**
 * The most recipients of a campaign processed in a day. It keeps the member's mailbox or LinkedIn account from being
 * flagged. It is only a guard: an AI stops at it, and a member may go beyond it.
 */
final readonly class DailyCap
{
    private function __construct(public int $sends)
    {
    }

    /**
     * @throws InvalidDailyCap
     */
    public static function of(int $sends): self
    {
        return $sends < 1 ? throw InvalidDailyCap::belowOne() : new self($sends);
    }

    #[\NoDiscard]
    public function isReachedBy(int $processed): bool
    {
        return $processed >= $this->sends;
    }

    /**
     * Null when the member set no cap.
     *
     * @throws InvalidDailyCap
     */
    public static function orNone(?int $sends): ?self
    {
        return null === $sends ? null : self::of($sends);
    }
}
