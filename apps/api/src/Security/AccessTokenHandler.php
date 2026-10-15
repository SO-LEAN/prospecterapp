<?php

namespace App\Security;

use Lcobucci\Clock\SystemClock;
use Lcobucci\JWT\Encoding\JoseEncoder;
use Lcobucci\JWT\Signer\Key\InMemory;
use Lcobucci\JWT\Signer\Rsa\Sha256;
use Lcobucci\JWT\Token\Parser;
use Lcobucci\JWT\UnencryptedToken;
use Lcobucci\JWT\Validation\Constraint\PermittedFor;
use Lcobucci\JWT\Validation\Constraint\SignedWith;
use Lcobucci\JWT\Validation\Constraint\StrictValidAt;
use Lcobucci\JWT\Validation\Validator;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Security\Core\Exception\BadCredentialsException;
use Symfony\Component\Security\Http\AccessToken\AccessTokenHandlerInterface;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;

/**
 * The API is a resource server: an access token of the authorization server (apps/auth, ADR 0034) is a JWT
 * signed with its private key, for the SPA, not expired; its subject is the member id. Checked here with the
 * public key only, without calling the authorization server.
 */
final readonly class AccessTokenHandler implements AccessTokenHandlerInterface
{
    public function __construct(
        #[Autowire(env: 'resolve:AUTH_PUBLIC_KEY')]
        private string $publicKey,
        #[Autowire(env: 'AUTH_CLIENT_ID')]
        private string $clientId,
    ) {
    }

    public function getUserBadgeFrom(#[\SensitiveParameter] string $accessToken): UserBadge
    {
        try {
            $token = new Parser(new JoseEncoder())->parse($accessToken);
        } catch (\Throwable) {
            throw new BadCredentialsException('Invalid access token.');
        }

        if (!$token instanceof UnencryptedToken || !new Validator()->validate($token, ...$this->constraints())) {
            throw new BadCredentialsException('Invalid access token.');
        }

        return new UserBadge((string) $token->claims()->get('sub'));
    }

    /**
     * @return list<SignedWith|StrictValidAt|PermittedFor>
     */
    private function constraints(): array
    {
        return [
            new SignedWith(new Sha256(), InMemory::file($this->publicKey)),
            new StrictValidAt(SystemClock::fromSystemTimezone()),
            new PermittedFor($this->clientId),
        ];
    }
}
