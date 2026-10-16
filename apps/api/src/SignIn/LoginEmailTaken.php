<?php

namespace App\SignIn;

/**
 * Another member signs in with this email: a login email is unique on the platform.
 */
final class LoginEmailTaken extends \DomainException
{
    public function __construct()
    {
        parent::__construct('Email already used');
    }
}
