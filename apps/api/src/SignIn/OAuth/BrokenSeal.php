<?php

namespace App\SignIn\OAuth;

/**
 * What the facade was given back is not what it sealed, for this purpose, still in time.
 */
final class BrokenSeal extends \RuntimeException
{
    public static function given(): self
    {
        return new self('The seal does not open.');
    }
}
