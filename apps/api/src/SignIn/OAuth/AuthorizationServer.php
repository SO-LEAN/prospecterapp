<?php

namespace App\SignIn\OAuth;

/**
 * The API as the authorization server the AIs see (RFC 8414), a facade over Cognito keeping nothing (ADR 0157): its
 * endpoints, named by the host of the API, as the protected resource names it.
 */
final readonly class AuthorizationServer
{
    public function __construct(private ProtectedResource $mcp)
    {
    }

    public function issuer(): string
    {
        return $this->mcp->authorizationServer();
    }

    public function authorizationEndpoint(): string
    {
        return $this->issuer().'/oauth/authorize';
    }

    public function tokenEndpoint(): string
    {
        return $this->issuer().'/oauth/token';
    }

    /**
     * Where Cognito sends the member back once signed in, the only address its client of the AIs declares.
     */
    public function callbackEndpoint(): string
    {
        return $this->issuer().'/oauth/callback';
    }
}
