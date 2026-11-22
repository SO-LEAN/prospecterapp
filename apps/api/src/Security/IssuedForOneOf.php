<?php

declare(strict_types=1);

namespace App\Security;

use Lcobucci\JWT\Token;
use Lcobucci\JWT\UnencryptedToken;
use Lcobucci\JWT\Validation\Constraint;
use Lcobucci\JWT\Validation\ConstraintViolation;

/**
 * An access token of Cognito names the client of the pool it was issued for (`client_id`): one of those the firewall
 * opens to.
 */
final readonly class IssuedForOneOf implements Constraint
{
    /**
     * @param list<string> $clientIds
     */
    public function __construct(private array $clientIds)
    {
    }

    public function assert(Token $token): void
    {
        if (!$token instanceof UnencryptedToken || !in_array($token->claims()->get('client_id'), $this->clientIds, true)) {
            throw ConstraintViolation::error('The token was issued for another client', $this);
        }
    }
}
