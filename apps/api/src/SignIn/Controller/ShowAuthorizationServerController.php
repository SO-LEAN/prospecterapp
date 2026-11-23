<?php

declare(strict_types=1);

namespace App\SignIn\Controller;

use App\Shared\JsonResponses;
use App\SignIn\OAuth\AuthorizationServer;
use App\SignIn\Resource\AuthorizationServerMetadataResource;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

/**
 * Public, outside the API and its contract. It tells how an AI signs its member in through the facade over Cognito
 * (RFC 8414).
 */
final readonly class ShowAuthorizationServerController
{
    public function __construct(private AuthorizationServer $authorizationServer, private JsonResponses $jsonResponses)
    {
    }

    #[Route('/.well-known/oauth-authorization-server', name: 'oauth_authorization_server', methods: ['GET'])]
    public function __invoke(): JsonResponse
    {
        return $this->jsonResponses->of(new AuthorizationServerMetadataResource(
            $this->authorizationServer->issuer(),
            $this->authorizationServer->authorizationEndpoint(),
            $this->authorizationServer->tokenEndpoint(),
        ));
    }
}
