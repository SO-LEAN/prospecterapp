<?php

declare(strict_types=1);

namespace App\Security;

use Lcobucci\JWT\Token;
use Lcobucci\JWT\Token\RegisteredClaims;
use Lcobucci\JWT\UnencryptedToken;
use Lcobucci\JWT\Validation\Constraint;
use Lcobucci\JWT\Validation\ConstraintViolation;

/**
 * A token must tell when it expires. LooseValidAt checks its time claims only when they are present, and a Cognito
 * access token has no "not before" claim for StrictValidAt.
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
