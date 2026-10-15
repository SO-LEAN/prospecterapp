<?php

namespace App\Shared;

use Symfony\Component\Uid\Uuid;

/**
 * The identity of what a client is about to create: a UUID v7, ordered by creation, put in the command.
 */
final readonly class Identities
{
    public function next(): string
    {
        return Uuid::v7()->toRfc4122();
    }
}
