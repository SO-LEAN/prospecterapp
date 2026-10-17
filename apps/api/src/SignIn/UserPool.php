<?php

namespace App\SignIn;

use Aws\CognitoIdentityProvider\CognitoIdentityProviderClient;
use Symfony\Contracts\Cache\CacheInterface;

/**
 * The Cognito user pool the members sign in with (ADR 0052), and its only client, the SPA. Both are known by
 * name: their ids are made by Cognito when they are created, then cached. A member is a user named by its
 * member id, who signs in with the email of its alias.
 */
final readonly class UserPool
{
    public const string CLIENT_NAME = 'prospecter-front';
    public const int PASSWORD_MINIMUM_LENGTH = 8;
    private const int LIST_SIZE = 60;

    public function __construct(
        private CognitoIdentityProviderClient $cognito,
        private CacheInterface $cache,
        public string $name,
        public string $region,
        private string $keysUrl,
    ) {
    }

    public function id(): string
    {
        return $this->cache->get($this->cacheKey('id'), fn (): string => $this->found()['Id'] ?? throw new \LogicException(sprintf('No user pool "%s": run app:user-pool:recreate', $this->name)));
    }

    public function clientId(): string
    {
        return $this->cache->get($this->cacheKey('client'), fn (): string => $this->clientNamed(self::CLIENT_NAME, $this->id()));
    }

    /**
     * What the tokens of the pool name as their issuer, the same locally as on AWS.
     */
    public function issuer(): string
    {
        return sprintf('https://cognito-idp.%s.amazonaws.com/%s', $this->region, $this->id());
    }

    /**
     * Where the public keys the tokens are signed with are published (JWKS).
     */
    public function keysUrl(): string
    {
        return sprintf($this->keysUrl, $this->id());
    }

    /**
     * Drops the pool and its users, and creates it empty with its client.
     */
    public function recreate(): void
    {
        $found = $this->found();

        if (null !== $found) {
            $this->cognito->deleteUserPool(['UserPoolId' => $found['Id']]);
        }

        $this->cache->delete($this->cacheKey('id'));
        $this->cache->delete($this->cacheKey('client'));
        $this->create();
    }

    private function create(): void
    {
        $id = $this->cognito->createUserPool([
            'PoolName' => $this->name,
            'AliasAttributes' => ['email'],
            'Policies' => ['PasswordPolicy' => [
                'MinimumLength' => self::PASSWORD_MINIMUM_LENGTH,
                'RequireUppercase' => false,
                'RequireLowercase' => false,
                'RequireNumbers' => false,
                'RequireSymbols' => false,
            ]],
            'AdminCreateUserConfig' => ['AllowAdminCreateUserOnly' => true],
        ])['UserPool']['Id'];

        // the SPA signs in by SRP and refreshes its tokens; the API alone, with its credentials, signs in by password (commands, tests)
        $this->cognito->createUserPoolClient([
            'UserPoolId' => $id,
            'ClientName' => self::CLIENT_NAME,
            'ExplicitAuthFlows' => ['ALLOW_USER_SRP_AUTH', 'ALLOW_REFRESH_TOKEN_AUTH', 'ALLOW_ADMIN_USER_PASSWORD_AUTH'],
            'PreventUserExistenceErrors' => 'ENABLED',
        ]);
    }

    /**
     * @return array{Id: string}|null
     */
    private function found(): ?array
    {
        $request = ['MaxResults' => self::LIST_SIZE];

        do {
            $result = $this->cognito->listUserPools($request);

            foreach ($result['UserPools'] ?? [] as $pool) {
                if ($this->name === $pool['Name']) {
                    return $pool;
                }
            }

            $request['NextToken'] = $result['NextToken'] ?? null;
        } while (null !== $request['NextToken']);

        return null;
    }

    private function clientNamed(string $name, string $poolId): string
    {
        foreach ($this->cognito->listUserPoolClients(['UserPoolId' => $poolId, 'MaxResults' => self::LIST_SIZE])['UserPoolClients'] ?? [] as $client) {
            if ($name === $client['ClientName']) {
                return $client['ClientId'];
            }
        }

        throw new \LogicException(sprintf('No client "%s" in the user pool "%s"', $name, $this->name));
    }

    private function cacheKey(string $what): string
    {
        return sprintf('user_pool.%s.%s', $this->name, $what);
    }
}
