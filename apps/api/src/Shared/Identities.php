<?php

declare(strict_types=1);

namespace App\Shared;

use Symfony\Component\Uid\Uuid;

/**
 * The id of what a client is about to create. It is a UUID v7, ordered by creation time, and the client puts it in the
 * command.
 */
final readonly class Identities
{
    public function next(): string
    {
        return Uuid::v7()->toRfc4122();
    }
}
