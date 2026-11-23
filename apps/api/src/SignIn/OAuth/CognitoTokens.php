<?php

declare(strict_types=1);

namespace App\SignIn\OAuth;

use App\Shared\Typed;
use App\SignIn\UserPool;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Contracts\HttpClient\HttpClientInterface;

/**
 * Cognito's token endpoint (its domain on AWS, MiniStack locally). The facade calls it for the AIs' client, with that
 * client's secret (ADR 0157). The facade returns Cognito's tokens to the AI unchanged.
 */
final readonly class CognitoTokens
{
    public function __construct(
        private UserPool $pool,
        private AuthorizationServer $authorizationServer,
        #[Autowire(service: 'app.cognito_oauth_http')]
        private HttpClientInterface $http,
        #[Autowire(env: 'COGNITO_OAUTH_URL')]
        private string $oauthUrl,
    ) {
    }

    /**
     * @return array<string, mixed>
     *
     * @throws GrantRefused
     */
    public function forCode(string $code, string $verifier): array
    {
        return $this->asked([
            'grant_type' => 'authorization_code',
            'code' => $code,
            'redirect_uri' => $this->authorizationServer->callbackEndpoint(),
            'code_verifier' => $verifier,
        ]);
    }

    /**
     * @return array<string, mixed>
     *
     * @throws GrantRefused
     */
    public function refreshed(string $refreshToken): array
    {
        return $this->asked(['grant_type' => 'refresh_token', 'refresh_token' => $refreshToken]);
    }

    /**
     * @param array<string, string> $grant
     *
     * @return array<string, mixed>
     */
    private function asked(array $grant): array
    {
        $answer = $this->http->request('POST', $this->oauthUrl.'/oauth2/token', [
            'auth_basic' => [$this->pool->mcpClientId(), $this->pool->mcpClientSecret()],
            'body' => $grant,
        ]);

        if (200 !== $answer->getStatusCode()) {
            throw GrantRefused::because(sprintf('Cognito refused the grant: %s', $answer->getContent(false)));
        }

        return Typed::document($answer->toArray());
    }
}
