<?php

declare(strict_types=1);

namespace Prospecter\PHPStan\EventStorming;

final class UnreadableCode extends \RuntimeException
{
    /**
     * @param list<string> $failures
     */
    public static function because(array $failures): self
    {
        return new self(implode("\n", ['The code could not be read for the event storming:', ...$failures]));
    }
}
