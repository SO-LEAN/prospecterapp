<?php

namespace App\Service\System;

class PhpFileSystemAdapter implements FileSystemAdapter
{
    public function tempnam(string $directory, string $prefix): string|false
    {
        return tempnam($directory, $prefix);
    }

    public function copy(string $source, string $destination, $context = null): bool
    {
        // a failed copy is reported by the return value, not by a warning
        return @copy($source, $destination, $context);
    }

    public function fileGetContents(string $filename, bool $useIncludePath = false, $context = null, int $offset = 0, ?int $length = null): string|false
    {
        return file_get_contents($filename, $useIncludePath, $context, $offset, $length);
    }
}
