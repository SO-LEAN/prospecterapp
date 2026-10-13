<?php

namespace App\Service\System;

interface FileSystemAdapter
{
    public function tempnam(string $directory, string $prefix): string|false;

    /**
     * @param resource|null $context
     */
    public function copy(string $source, string $destination, $context = null): bool;

    /**
     * @param resource|null $context
     */
    public function fileGetContents(string $filename, bool $useIncludePath = false, $context = null, int $offset = 0, ?int $length = null): string|false;
}
