<?php

namespace App\Security;

use Lcobucci\JWT\Token;
use Lcobucci\JWT\Token\RegisteredClaims;
use Lcobucci\JWT\UnencryptedToken;
use Lcobucci\JWT\Validation\Constraint;
use Lcobucci\JWT\Validation\ConstraintViolation;

/**
 * A token tells when it expires: the moments of LooseValidAt are only checked when present, and an access
 * token of Cognito has no "not before" for StrictValidAt.
 */
final readonly class Expires implements Constraint
{
    public function assert(Token $token): void
    {
        if (!$token instanceof UnencryptedToken || !$token->claims()->has(RegisteredClaims::EXPIRATION_TIME)) {
            throw ConstraintViolation::error('"Expiration Time" claim missing', $this);
        }
    }
}
