<?php

declare(strict_types=1);

namespace Tests\PHPStan\EventStorming;

use PHPStan\Command\Output;
use PHPStan\Command\OutputStyle;

/**
 * The console the formatter writes on, which keeps what it wrote: the raw document, and the lines that say why there
 * is none.
 *
 * @phpstan-ignore phpstanApi.interface (the formatter takes this console, and the test reads what it wrote)
 */
final class WrittenOutput implements Output
{
    public private(set) string $raw = '';

    /** @var list<string> */
    public private(set) array $lines = [];

    public function writeFormatted(string $message): void
    {
        $this->lines[] = $message;
    }

    public function writeLineFormatted(string $message): void
    {
        $this->lines[] = $message;
    }

    public function writeRaw(string $message): void
    {
        $this->raw .= $message;
    }

    public function getStyle(): OutputStyle
    {
        throw new \LogicException('The event storming writes no styled output');
    }

    public function isVerbose(): bool
    {
        return false;
    }

    public function isVeryVerbose(): bool
    {
        return false;
    }

    public function isDebug(): bool
    {
        return false;
    }

    public function isDecorated(): bool
    {
        return false;
    }
}
