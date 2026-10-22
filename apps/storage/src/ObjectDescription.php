<?php

namespace App;

/**
 * What the bucket tells of an object without reading it: its size in bytes and its media type.
 */
final readonly class ObjectDescription
{
    public function __construct(public int $size, public string $mediaType)
    {
    }
}
