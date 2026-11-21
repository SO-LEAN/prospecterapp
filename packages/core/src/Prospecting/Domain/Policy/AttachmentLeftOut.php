<?php

declare(strict_types=1);

namespace Prospecter\Core\Prospecting\Domain\Policy;

/**
 * A file of an imported e-mail its exchange does not hold, told in its text for the member to open it in Gmail
 * (ADR 0182).
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
     * In megabytes from one of them, in kilobytes under it, rounded.
     */
    private static function sizeOf(int $bytes): string
    {
        return $bytes < self::MEGABYTE ? sprintf('%d Ko', round($bytes / self::KILOBYTE)) : sprintf('%d Mo', round($bytes / self::MEGABYTE));
    }
}
