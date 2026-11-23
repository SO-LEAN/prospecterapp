<?php

declare(strict_types=1);

namespace Prospecter\Core\SharedKernel\Application\Port;

interface Storage
{
    /**
     * Stores the file and returns its public URL. The file may move away from its current path.
     */
    public function add(\SplFileInfo $file): string;

    public function remove(string $url): void;
}
