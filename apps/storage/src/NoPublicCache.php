<?php

declare(strict_types=1);

namespace App;

/**
 * Locally, nginx serves the bucket without keeping copies.
 */
final readonly class NoPublicCache implements PublicCache
{
    public function forget(array $keys): void
    {
    }
}
