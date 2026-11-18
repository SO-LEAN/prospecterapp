<?php

namespace App\SignIn\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

/**
 * The proof of the spike #202: where an AI finds the authorization server of /mcp (RFC 9728).
 */
final class ShowProtectedResourceController extends AbstractController
{
    public function __construct(
        #[Autowire(param: 'app_api_host')]
        private readonly string $apiHost,
    ) {
    }

    #[Route('/.well-known/oauth-protected-resource/mcp', methods: ['GET'])]
    #[Route('/.well-known/oauth-protected-resource', methods: ['GET'])]
    public function __invoke(): JsonResponse
    {
        $api = 'https://'.$this->apiHost;

        return new JsonResponse([
            'resource' => $api.'/mcp',
            'authorization_servers' => [$api],
            'bearer_methods_supported' => ['header'],
        ]);
    }
}
