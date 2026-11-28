<?php

declare(strict_types=1);

namespace Prospecter\PHPStan\EventStorming;

final class SharedReadModelLabels extends \RuntimeException
{
    /**
     * @param list<string> $queries each group of queries whose views share a label, prefixed with their context
     */
    public static function named(array $queries): self
    {
        return new self(implode("\n", ['These queries give their views the same label:', ...$queries]));
    }
}
