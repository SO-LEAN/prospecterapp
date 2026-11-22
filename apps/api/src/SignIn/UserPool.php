<?php

declare(strict_types=1);

namespace App\SignIn;

use App\SignIn\OAuth\AuthorizationServer;
use Aws\CognitoIdentityProvider\CognitoIdentityProviderClient;
use Symfony\Contracts\Cache\CacheInterface;

/**
 * The Cognito user pool the members sign in with (ADR 0052), and its clients: the SPA, and the AIs signing their
 * member in through the facade of the API (ADR 0157), its secret known to the API alone. They are known by name:
 * their ids and the secret are made by Cognito when they are created, then cached. A member is a user named by its
 * member id, who signs in with the email of its alias.
 */
final readonly class UserPool
{
    public const string CLIENT_NAME = 'prospecter-front';
    public const string MCP_CLIENT_NAME = 'prospecter-mcp';
    public const array CLIENT_NAMES_OF_THE_MCP_SERVER = [self::CLIENT_NAME, self::MCP_CLIENT_NAME];
    public const int PASSWORD_MINIMUM_LENGTH = 8;
    private const int LIST_SIZE = 60;

    public function __construct(
        private CognitoIdentityProviderClient $cognito,
        private CacheInterface $cache,
        public string $name,
        public string $region,
        private string $keysUrl,
        private AuthorizationServer $authorizationServer,
    ) {
    }

    public function id(): string
    {
        return $this->cache->get($this->cacheKey('id'), fn (): string => $this->found()['Id'] ?? throw new \LogicException(sprintf('No user pool "%s": run app:user-pool:recreate', $this->name)));
    }

    public function clientId(): string
    {
        return $this->clientIdNamed(self::CLIENT_NAME);
    }

    public function mcpClientId(): string
    {
        return $this->clientIdNamed(self::MCP_CLIENT_NAME);
    }

    public function clientIdNamed(string $name): string
    {
        return $this->cache->get($this->cacheKey('client.'.$name), fn (): string => $this->clientNamed($name, $this->id()));
    }

    public function mcpClientSecret(): string
    {
        return $this->cache->get($this->cacheKey('mcp-secret'), fn (): string => $this->cognito->describeUserPoolClient(['UserPoolId' => $this->id(), 'ClientId' => $this->mcpClientId()])['UserPoolClient']['ClientSecret']);
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
        $this->cache->delete($this->cacheKey('client.'.self::CLIENT_NAME));
        $this->cache->delete($this->cacheKey('client.'.self::MCP_CLIENT_NAME));
        $this->cache->delete($this->cacheKey('mcp-secret'));
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

        // the AIs sign in on the sign-in page of Cognito, by the code with PKCE, through the facade of the API
        $this->cognito->createUserPoolClient([
            'UserPoolId' => $id,
            'ClientName' => self::MCP_CLIENT_NAME,
            'GenerateSecret' => true,
            'ExplicitAuthFlows' => ['ALLOW_REFRESH_TOKEN_AUTH'],
            'AllowedOAuthFlowsUserPoolClient' => true,
            'AllowedOAuthFlows' => ['code'],
            'AllowedOAuthScopes' => ['openid'],
            'CallbackURLs' => [$this->authorizationServer->callbackEndpoint()],
            'SupportedIdentityProviders' => ['COGNITO'],
            'EnableTokenRevocation' => true,
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

            $pool = array_find($result['UserPools'] ?? [], fn (array $pool): bool => $this->name === $pool['Name']);

            if (null !== $pool) {
                return $pool;
            }

            $request['NextToken'] = $result['NextToken'] ?? null;
        } while (null !== $request['NextToken']);

        return null;
    }

    private function clientNamed(string $name, string $poolId): string
    {
        $clients = $this->cognito->listUserPoolClients(['UserPoolId' => $poolId, 'MaxResults' => self::LIST_SIZE])['UserPoolClients'] ?? [];
        $client = array_find($clients, static fn (array $client): bool => $name === $client['ClientName']);

        return $client['ClientId'] ?? throw new \LogicException(sprintf('No client "%s" in the user pool "%s"', $name, $this->name));
    }

    private function cacheKey(string $what): string
    {
        return sprintf('user_pool.%s.%s', $this->name, $what);
    }
}
