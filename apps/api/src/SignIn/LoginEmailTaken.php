<?php

declare(strict_types=1);

namespace App\SignIn;

/**
 * Another member already signs in with this email. A login email is unique on the platform.
 */
final class LoginEmailTaken extends \DomainException
{
    public function __construct()
    {
        parent::__construct('Email already used');
    }
}
