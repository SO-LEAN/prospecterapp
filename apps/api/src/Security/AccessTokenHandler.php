<?php

namespace App\Security;

use App\SignIn\SigningKeys;
use App\SignIn\UserPool;
use Lcobucci\JWT\Encoding\JoseEncoder;
use Lcobucci\JWT\Signer\Key\InMemory;
use Lcobucci\JWT\Signer\Rsa\Sha256;
use Lcobucci\JWT\Token\Parser;
use Lcobucci\JWT\UnencryptedToken;
use Lcobucci\JWT\Validation\Constraint;
use Lcobucci\JWT\Validation\Constraint\HasClaimWithValue;
use Lcobucci\JWT\Validation\Constraint\IssuedBy;
use Lcobucci\JWT\Validation\Constraint\LooseValidAt;
use Lcobucci\JWT\Validation\Constraint\SignedWith;
use Lcobucci\JWT\Validation\Validator;
use Psr\Clock\ClockInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Security\Core\Exception\BadCredentialsException;
use Symfony\Component\Security\Http\AccessToken\AccessTokenHandlerInterface;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;

/**
 * The API is a resource server (ADR 0052): an access token of the user pool is a JWT signed with one of its
 * published keys, issued by it for the SPA, not expired; its user name is the member id. Checked without
 * calling Cognito, but for its keys, read once and cached.
 */
final readonly class AccessTokenHandler implements AccessTokenHandlerInterface
{
    public function __construct(
        private SigningKeys $keys,
        private UserPool $pool,
        #[Autowire(service: 'app.token_clock')]
        private ClockInterface $clock,
    ) {
    }

    public function getUserBadgeFrom(#[\SensitiveParameter] string $accessToken): UserBadge
    {
        try {
            $token = new Parser(new JoseEncoder())->parse($accessToken);
        } catch (\Throwable) {
            throw new BadCredentialsException('Invalid access token.');
        }

        if (!$token instanceof UnencryptedToken || !new Validator()->validate($token, ...$this->constraintsFor($token))) {
            throw new BadCredentialsException('Invalid access token.');
        }

        return new UserBadge((string) $token->claims()->get('username'));
    }

    /**
     * @return list<Constraint>
     */
    private function constraintsFor(UnencryptedToken $token): array
    {
        $key = $this->keys->named((string) $token->headers()->get('kid'));

        if (null === $key) {
            throw new BadCredentialsException('Invalid access token.');
        }

        return [
            new SignedWith(new Sha256(), InMemory::plainText($key)),
            new IssuedBy($this->pool->issuer()),
            new HasClaimWithValue('token_use', 'access'),
            new HasClaimWithValue('client_id', $this->pool->clientId()),
            new Expires(),
            new LooseValidAt($this->clock),
        ];
    }
}
