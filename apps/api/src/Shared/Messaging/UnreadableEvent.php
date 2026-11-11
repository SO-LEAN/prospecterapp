<?php

namespace App\Shared\Messaging;

/**
 * The worker cannot read the event it was given: its shape, its detail-type or its version are unknown, or its
 * detail does not fit the class it names. Another attempt would read it no better.
 */
final class UnreadableEvent extends \RuntimeException
{
    public static function because(\Throwable $failure): self
    {
        return new self(sprintf('The event cannot be read: %s', $failure->getMessage()), previous: $failure);
    }
}
