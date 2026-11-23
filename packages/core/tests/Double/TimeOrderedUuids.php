<?php

declare(strict_types=1);

namespace Tests\Core\Double;

/**
 * Version 7 UUIDs, like the ones the real repositories generate. They follow the creation order, so that "newest
 * first" can sort by id. Each id comes a millisecond after the one before, from a fixed moment, because the
 * scenarios read no real clock.
 */
final class TimeOrderedUuids
{
    /** 2026-01-01 00:00:00 UTC. */
    private const int FIRST_MILLISECOND = 1_767_225_600_000;

    private int $sequence = 0;

    public function next(): string
    {
        $milliseconds = self::FIRST_MILLISECOND + $this->sequence;
        $hex = sprintf('%012x', $milliseconds).sprintf('7%03x', $this->sequence++ % 0x1000).sprintf('%04x', 0x8000 | random_int(0, 0x3FFF)).bin2hex(random_bytes(6));

        return implode('-', [substr($hex, 0, 8), substr($hex, 8, 4), substr($hex, 12, 4), substr($hex, 16, 4), substr($hex, 20, 12)]);
    }
}
