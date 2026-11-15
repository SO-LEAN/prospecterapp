<?php

namespace App\Shared;

/**
 * A service of AWS the api needs refuses or fails: the gesture cannot be done for now, whatever the member asked,
 * named by its reason for the client to word it (ADR 0073).
 */
final class ServiceUnavailable extends \RuntimeException
{
    private const string SECRETS = 'secretsUnavailable';

    private function __construct(string $message, public readonly string $reason, \Throwable $cause)
    {
        parent::__construct($message, previous: $cause);
    }

    public static function secrets(\Throwable $cause): self
    {
        return new self('The secrets of the accounts cannot be kept nor read for now', self::SECRETS, $cause);
    }
}
