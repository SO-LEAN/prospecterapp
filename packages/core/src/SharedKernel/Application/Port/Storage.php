<?php

declare(strict_types=1);

namespace Prospecter\Core\SharedKernel\Application\Port;

interface Storage
{
    /**
     * Stores the file (it may be moved away from its current path) and returns its public URL.
     */
    public function add(\SplFileInfo $file): string;

    public function remove(string $url): void;
}
