<?php

declare(strict_types=1);

namespace Tests\Core\Architecture;

/**
 * The paths of the files in a directory and its subdirectories.
 */
final class FilesUnder
{
    /**
     * @return list<string>
     */
    public static function the(string $directory): array
    {
        $paths = [];

        foreach (new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($directory, \FilesystemIterator::SKIP_DOTS)) as $file) {
            if ($file instanceof \SplFileInfo) {
                $paths[] = $file->getPathname();
            }
        }

        return $paths;
    }
}
