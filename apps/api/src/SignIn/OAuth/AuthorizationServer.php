<?php

declare(strict_types=1);

namespace App\SignIn\OAuth;

/**
 * The API as the authorization server that the AIs see (RFC 8414). It is a facade over Cognito and keeps nothing
 * (ADR 0157). Its endpoints are named after the API's host, as the protected resource names them.
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
     * Where Cognito sends the member back once they are signed in. The AIs' Cognito client declares no other address.
     */
    public function callbackEndpoint(): string
    {
        return $this->issuer().'/oauth/callback';
    }
}
