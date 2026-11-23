<?php

declare(strict_types=1);

namespace Prospecter\Core\Prospecting\Domain\Policy;

/**
 * A file of an imported e-mail that its exchange does not hold. The exchange's text names it, so that the member opens
 * it in Gmail (ADR 0182).
 */
final readonly class AttachmentLeftOut
{
    private const int KILOBYTE = 1024;
    private const int MEGABYTE = 1024 * self::KILOBYTE;

    public static function mention(string $name, int $size): string
    {
        return sprintf('Pièce jointe non importée : %s (%s), à ouvrir dans Gmail', $name, self::sizeOf($size));
    }

    /**
     * Rounds the size in megabytes from one megabyte, and in kilobytes below. A size under a kilobyte says so instead
     * of rounding to nothing.
     */
    private static function sizeOf(int $bytes): string
    {
        return match (true) {
            $bytes < self::KILOBYTE => 'moins de 1 Ko',
            $bytes < self::MEGABYTE => sprintf('%d Ko', round($bytes / self::KILOBYTE)),
            default => sprintf('%d Mo', round($bytes / self::MEGABYTE)),
        };
    }
}
