<?php

namespace App\SignIn\OAuth;

use App\SignIn\UserPool;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

/**
 * The sign-in page of Cognito (its managed login, on the domain of the pool; MiniStack locally) for the client of the
 * AIs, by the code with a PKCE of the API (ADR 0157).
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
     * The page the browser of the member is sent to, Cognito giving back the state with its code.
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
