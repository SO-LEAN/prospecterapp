<?php

declare(strict_types=1);

namespace Tests\Core\Double;

/**
 * UUID version 7, like the real repositories generate: ordered by creation, so that "newest first" can sort by id.
 */
final class TimeOrderedUuids
{
    private int $sequence = 0;

    public function next(): string
    {
        $milliseconds = (int) (microtime(true) * 1000);
        $hex = sprintf('%012x', $milliseconds).sprintf('7%03x', $this->sequence++ % 0x1000).sprintf('%04x', 0x8000 | random_int(0, 0x3FFF)).bin2hex(random_bytes(6));

        return implode('-', [substr($hex, 0, 8), substr($hex, 8, 4), substr($hex, 12, 4), substr($hex, 16, 4), substr($hex, 20, 12)]);
    }
}
