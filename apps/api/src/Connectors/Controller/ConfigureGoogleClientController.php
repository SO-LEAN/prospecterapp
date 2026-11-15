<?php

namespace App\Connectors\Controller;

use App\Connectors\GoogleClientReader;
use App\Connectors\Input\GoogleClientInput;
use App\Connectors\Resource\GoogleClientResource;
use App\Security\User;
use App\Shared\CommandBus;
use App\Shared\Resource\ProblemResource;
use Nelmio\ApiDocBundle\Attribute\Model;
use OpenApi\Attributes as OA;
use Prospecter\Core\Connectors\Application\ConfigureClient\ConfigureClient;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Clock\ClockInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[OA\Tag('Connectors')]
#[IsGranted('ROLE_ADMIN')]
final class ConfigureGoogleClientController extends AbstractController
{
    private const string GOOGLE = 'google';

    public function __construct(
        private readonly CommandBus $commandBus,
        private readonly ClockInterface $clock,
        private readonly GoogleClientReader $googleClientReader,
    ) {
    }

    #[Route('/api/me/organization/clients/google', name: 'api_me_configure_google_client', methods: ['PATCH'])]
    #[OA\Patch(summary: 'Configure the client of Google of my account, or another one: the members connect through it from now on, those connected through another client reconnect')]
    #[OA\Response(response: 200, description: 'The client of Google of my account', content: new Model(type: GoogleClientResource::class))]
    #[OA\Response(response: 401, description: 'Missing or invalid access token')]
    #[OA\Response(response: 403, description: 'The signed-in user is no administrator of the account', content: new Model(type: ProblemResource::class))]
    #[OA\Response(response: 422, description: 'A client without its id or its secret', content: new Model(type: ProblemResource::class))]
    public function __invoke(#[MapRequestPayload] GoogleClientInput $input, #[CurrentUser] User $user): JsonResponse
    {
        $this->commandBus->dispatch(new ConfigureClient(
            memberId: $user->memberId(),
            provider: self::GOOGLE,
            clientId: $input->clientId ?? throw new \LogicException('A client validated has its id'),
            clientSecret: $input->clientSecret ?? throw new \LogicException('A client validated has its secret'),
            executedAt: $this->clock->now(),
        ));

        return $this->json($this->googleClientReader->read($user->memberId()));
    }
}
