<?php

declare(strict_types=1);

namespace App\Security;

use App\Shared\Typed;
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
 * published keys, issued by it for one of the clients the firewall opens to (the SPA; the AIs too on the MCP server,
 * ADR 0157), not expired; its user name is the member id. Checked without calling Cognito, but for its keys, read
 * once and cached.
 */
final readonly class AccessTokenHandler implements AccessTokenHandlerInterface
{
    /**
     * @param list<string> $clientNames
     */
    public function __construct(
        private SigningKeys $keys,
        private UserPool $pool,
        #[Autowire(service: 'app.token_clock')]
        private ClockInterface $clock,
        private array $clientNames = [UserPool::CLIENT_NAME],
    ) {
    }

    public function getUserBadgeFrom(#[\SensitiveParameter] string $accessToken): UserBadge
    {
        try {
            $token = new Parser(new JoseEncoder())->parse(Typed::filledString($accessToken));
        } catch (\Throwable) {
            throw new BadCredentialsException('Invalid access token.');
        }

        if (!$token instanceof UnencryptedToken || !new Validator()->validate($token, ...$this->constraintsFor($token))) {
            throw new BadCredentialsException('Invalid access token.');
        }

        return new UserBadge(self::usernameOf($token));
    }

    private static function usernameOf(UnencryptedToken $token): string
    {
        $username = $token->claims()->get('username');

        return is_string($username) ? $username : throw new BadCredentialsException('Invalid access token.');
    }

    /**
     * @return list<Constraint>
     */
    private function constraintsFor(UnencryptedToken $token): array
    {
        $kid = $token->headers()->get('kid');
        $key = is_string($kid) ? $this->keys->named($kid) : null;

        if (null === $key) {
            throw new BadCredentialsException('Invalid access token.');
        }

        return [
            new SignedWith(new Sha256(), InMemory::plainText(Typed::filledString($key))),
            new IssuedBy(Typed::filledString($this->pool->issuer())),
            new HasClaimWithValue('token_use', 'access'),
            new IssuedForOneOf(array_map($this->pool->clientIdNamed(...), $this->clientNames)),
            new Expires(),
            new LooseValidAt($this->clock),
        ];
    }
}
