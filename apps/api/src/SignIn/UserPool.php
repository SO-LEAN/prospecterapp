<?php

declare(strict_types=1);

namespace App\SignIn;

use App\Shared\Typed;
use App\SignIn\OAuth\AuthorizationServer;
use Aws\CognitoIdentityProvider\CognitoIdentityProviderClient;
use Symfony\Contracts\Cache\CacheInterface;

/**
 * The Cognito user pool that the members sign in with (ADR 0052), and its clients. One client is the SPA. The other
 * one is for the AIs, which sign their member in through the API's facade (ADR 0157). Only the API knows that
 * client's secret. The API finds the pool and its clients by name. Cognito makes their ids and the secret when it
 * creates them, and the API caches them. A member is a user named after their member id, who signs in with their
 * alias's email.
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
        return $this->cache->get($this->cacheKey('id'), fn (): string => $this->foundId() ?? throw new \LogicException(sprintf('No user pool "%s": run app:user-pool:recreate', $this->name)));
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
        return $this->cache->get($this->cacheKey('mcp-secret'), fn (): string => Typed::string(Typed::document($this->cognito->describeUserPoolClient(['UserPoolId' => $this->id(), 'ClientId' => $this->mcpClientId()])['UserPoolClient'])['ClientSecret']));
    }

    /**
     * The issuer that the pool's tokens name. It is the same locally and on AWS.
     */
    public function issuer(): string
    {
        return sprintf('https://cognito-idp.%s.amazonaws.com/%s', $this->region, $this->id());
    }

    /**
     * Where the public keys that sign the tokens are published (JWKS).
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
        $foundId = $this->foundId();

        if (null !== $foundId) {
            $this->cognito->deleteUserPool(['UserPoolId' => $foundId]);
        }

        $this->cache->delete($this->cacheKey('id'));
        $this->cache->delete($this->cacheKey('client.'.self::CLIENT_NAME));
        $this->cache->delete($this->cacheKey('client.'.self::MCP_CLIENT_NAME));
        $this->cache->delete($this->cacheKey('mcp-secret'));
        $this->create();
    }

    private function create(): void
    {
        $created = $this->cognito->createUserPool([
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
        ]);
        $id = Typed::string(Typed::document($created['UserPool'])['Id']);

        // The SPA signs in by SRP and refreshes its tokens. Only the API, with its credentials, signs in by password (commands, tests).
        $this->cognito->createUserPoolClient([
            'UserPoolId' => $id,
            'ClientName' => self::CLIENT_NAME,
            'ExplicitAuthFlows' => ['ALLOW_USER_SRP_AUTH', 'ALLOW_REFRESH_TOKEN_AUTH', 'ALLOW_ADMIN_USER_PASSWORD_AUTH'],
            'PreventUserExistenceErrors' => 'ENABLED',
        ]);

        // The AIs sign in on Cognito's sign-in page, with the authorization code and PKCE, through the API's facade.
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

    private function foundId(): ?string
    {
        $nextToken = null;

        do {
            $result = $this->cognito->listUserPools(['MaxResults' => self::LIST_SIZE, ...(null === $nextToken ? [] : ['NextToken' => $nextToken])]);

            $pool = array_find(Typed::documents($result['UserPools'] ?? []), fn (array $pool): bool => $this->name === $pool['Name']);

            if (null !== $pool) {
                return Typed::string($pool['Id']);
            }

            $nextToken = Typed::stringOrNull($result['NextToken'] ?? null);
        } while (null !== $nextToken);

        return null;
    }

    private function clientNamed(string $name, string $poolId): string
    {
        $clients = $this->cognito->listUserPoolClients(['UserPoolId' => $poolId, 'MaxResults' => self::LIST_SIZE])['UserPoolClients'] ?? [];
        $client = array_find(Typed::documents($clients), static fn (array $client): bool => $name === $client['ClientName']);

        return null === $client ? throw new \LogicException(sprintf('No client "%s" in the user pool "%s"', $name, $this->name)) : Typed::string($client['ClientId']);
    }

    private function cacheKey(string $what): string
    {
        return sprintf('user_pool.%s.%s', $this->name, $what);
    }
}
