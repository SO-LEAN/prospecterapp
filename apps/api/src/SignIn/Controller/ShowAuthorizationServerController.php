<?php

declare(strict_types=1);

namespace App\SignIn\Controller;

use App\SignIn\OAuth\AuthorizationServer;
use App\SignIn\Resource\AuthorizationServerMetadataResource;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

/**
 * Public, outside the API and its contract. It tells how an AI signs its member in through the facade over Cognito
 * (RFC 8414).
 */
final class ShowAuthorizationServerController extends AbstractController
{
    public function __construct(private readonly AuthorizationServer $authorizationServer)
    {
    }

    #[Route('/.well-known/oauth-authorization-server', name: 'oauth_authorization_server', methods: ['GET'])]
    public function __invoke(): JsonResponse
    {
        return $this->json(new AuthorizationServerMetadataResource(
            $this->authorizationServer->issuer(),
            $this->authorizationServer->authorizationEndpoint(),
            $this->authorizationServer->tokenEndpoint(),
        ));
    }
}
