<?php

namespace App;

/**
 * What keeps copies of the files in front of the bucket: a removed file must not be served from it any more.
 */
interface PublicCache
{
    /**
     * @param list<string> $keys the keys of the removed files
     */
    public function forget(array $keys): void;
}
