<?php

declare(strict_types=1);

namespace App\SignIn\OAuth;

/**
 * The facade got back something it did not seal for this purpose, or the seal has expired.
 */
final class BrokenSeal extends \RuntimeException
{
    public static function given(): self
    {
        return new self('The seal does not open.');
    }
}
