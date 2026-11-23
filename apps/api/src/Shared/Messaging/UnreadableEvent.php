<?php

declare(strict_types=1);

namespace App\Shared\Messaging;

/**
 * The worker cannot read the event it received. Its shape, detail-type or version is unknown, or its detail does not
 * fit the class it names. Another attempt would not read it better.
 */
final class UnreadableEvent extends \RuntimeException
{
    public static function because(\Throwable $failure): self
    {
        return new self(sprintf('The event cannot be read: %s', $failure->getMessage()), previous: $failure);
    }
}
