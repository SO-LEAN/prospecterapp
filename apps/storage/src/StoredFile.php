<?php

namespace App;

/**
 * A file as the bucket keeps it: its bytes and the media type it is served with.
 */
final readonly class StoredFile
{
    public function __construct(public string $contents, public string $contentType)
    {
    }
}
