<?php

declare(strict_types=1);

namespace App;

/**
 * Whatever keeps copies of the files in front of the bucket. It must stop serving a removed file.
 */
interface PublicCache
{
    /**
     * @param list<string> $keys the keys of the removed files
     */
    public function forget(array $keys): void;
}
