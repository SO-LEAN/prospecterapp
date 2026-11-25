<?php

declare(strict_types=1);

namespace Prospecter\PHPStan\EventStorming;

final class UntitledUseCases extends \RuntimeException
{
    /**
     * @param list<string> $useCases
     */
    public static function named(array $useCases): self
    {
        return new self(implode("\n", ['No feature names these use cases:', ...$useCases]));
    }
}
