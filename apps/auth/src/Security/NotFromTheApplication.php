<?php

namespace App\Security;

use Symfony\Component\Security\Core\Exception\AuthenticationException;

/**
 * A sign-in sent by another origin than the SPA: a page of another site trying to sign the visitor in
 * with its own credentials (login CSRF).
 */
final class NotFromTheApplication extends AuthenticationException
{
    public function getMessageKey(): string
    {
        return 'Sign-in is only accepted from the application.';
    }
}
