<?php

namespace App\Shared;

/**
 * A service the api needs, of AWS or outside, refuses or fails: the gesture cannot be done for now, whatever the
 * member asked, named by its reason for the client to word it (ADR 0073).
 */
final class ServiceUnavailable extends \RuntimeException
{
    private const string SECRETS = 'secretsUnavailable';
    private const string GOOGLE = 'googleUnreachable';

    private function __construct(string $message, public readonly string $reason, \Throwable $cause)
    {
        parent::__construct($message, previous: $cause);
    }

    public static function secrets(\Throwable $cause): self
    {
        return new self('The secrets of the accounts cannot be kept nor read for now', self::SECRETS, $cause);
    }

    public static function google(\Throwable $cause): self
    {
        return new self('Google does not answer for now', self::GOOGLE, $cause);
    }
}
