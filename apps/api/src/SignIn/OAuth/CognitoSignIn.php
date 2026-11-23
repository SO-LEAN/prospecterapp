<?php

declare(strict_types=1);

namespace App\SignIn\OAuth;

use App\SignIn\UserPool;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

/**
 * Cognito's sign-in page (its managed login, on the pool's domain; MiniStack locally) for the AIs' client. It uses the
 * authorization code with the API's own PKCE (ADR 0157).
 */
final readonly class CognitoSignIn
{
    public function __construct(
        private UserPool $pool,
        private AuthorizationServer $authorizationServer,
        #[Autowire(env: 'COGNITO_SIGN_IN_URL')]
        private string $signInUrl,
    ) {
    }

    /**
     * The page that the member's browser goes to. Cognito gives back the state with its code.
     */
    public function pageFor(string $state, string $codeChallenge): string
    {
        return $this->signInUrl.'/oauth2/authorize?'.http_build_query([
            'response_type' => 'code',
            'client_id' => $this->pool->mcpClientId(),
            'redirect_uri' => $this->authorizationServer->callbackEndpoint(),
            'scope' => 'openid',
            'state' => $state,
            'code_challenge' => $codeChallenge,
            'code_challenge_method' => Pkce::METHOD,
        ]);
    }
}
