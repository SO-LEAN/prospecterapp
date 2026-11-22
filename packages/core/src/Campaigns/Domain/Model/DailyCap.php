<?php

declare(strict_types=1);

namespace Prospecter\Core\Campaigns\Domain\Model;

use Prospecter\Core\Campaigns\Domain\Exception\InvalidDailyCap;

/**
 * The most recipients of a campaign processed a day, so that the member's mailbox or LinkedIn account is not flagged:
 * a guard only, which an AI stops at and a member may go beyond.
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

    /**
     * None when the member set none.
     *
     * @throws InvalidDailyCap
     */
    public static function orNone(?int $sends): ?self
    {
        return null === $sends ? null : self::of($sends);
    }
}
