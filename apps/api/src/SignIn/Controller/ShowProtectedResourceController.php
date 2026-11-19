<?php

namespace App\SignIn\Controller;

use App\SignIn\OAuth\ProtectedResource;
use App\SignIn\Resource\ProtectedResourceMetadataResource;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

/**
 * Public, outside the API and its contract: where an AI the MCP server sent away finds the server to sign its
 * member in on (RFC 9728).
 */
final class ShowProtectedResourceController extends AbstractController
{
    public function __construct(private readonly ProtectedResource $mcp)
    {
    }

    #[Route('/.well-known/oauth-protected-resource/mcp', name: 'oauth_protected_resource', methods: ['GET'])]
    #[Route('/.well-known/oauth-protected-resource', name: 'oauth_protected_resource_of_the_host', methods: ['GET'])]
    public function __invoke(): JsonResponse
    {
        return $this->json(new ProtectedResourceMetadataResource($this->mcp->resource(), [$this->mcp->authorizationServer()]));
    }
}
