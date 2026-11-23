<?php

declare(strict_types=1);

namespace App\Connectors\Controller;

use App\Connectors\GoogleClientReader;
use App\Connectors\Resource\GoogleClientResource;
use App\Security\User;
use App\Shared\CommandBus;
use App\Shared\Resource\ProblemResource;
use Nelmio\ApiDocBundle\Attribute\Model;
use OpenApi\Attributes as OA;
use Prospecter\Core\Connectors\Application\RemoveClient\RemoveClient;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Clock\ClockInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[OA\Tag('Connectors')]
#[IsGranted('ROLE_ADMIN')]
final class RemoveGoogleClientController extends AbstractController
{
    private const string GOOGLE = 'google';

    public function __construct(
        private readonly CommandBus $commandBus,
        private readonly ClockInterface $clock,
        private readonly GoogleClientReader $googleClientReader,
    ) {
    }

    #[Route('/api/me/organization/clients/google', name: 'api_me_remove_google_client', methods: ['DELETE'])]
    #[OA\Delete(summary: 'Remove my account\'s Google client. Google is no longer offered to its members')]
    #[OA\Response(response: 200, description: 'My account, without its Google client', content: new Model(type: GoogleClientResource::class))]
    #[OA\Response(response: 401, description: 'Missing or invalid access token')]
    #[OA\Response(response: 403, description: 'The signed-in user is not the account\'s administrator', content: new Model(type: ProblemResource::class))]
    #[OA\Response(response: 404, description: 'My account has no Google client', content: new Model(type: ProblemResource::class))]
    #[OA\Response(response: 503, description: 'The client\'s secret cannot be stored or read right now (`secretsUnavailable`)', content: new Model(type: ProblemResource::class))]
    public function __invoke(#[CurrentUser] User $user): JsonResponse
    {
        $this->commandBus->dispatch(new RemoveClient($user->memberId(), self::GOOGLE, $this->clock->now()));

        return $this->json($this->googleClientReader->read($user));
    }
}
