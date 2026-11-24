<?php

declare(strict_types=1);

namespace App\Connectors\Controller;

use App\Connectors\GoogleClientReader;
use App\Connectors\Input\GoogleClientInput;
use App\Connectors\Resource\GoogleClientResource;
use App\Security\User;
use App\Shared\CommandBus;
use App\Shared\JsonResponses;
use App\Shared\Resource\ProblemResource;
use Nelmio\ApiDocBundle\Attribute\Model;
use OpenApi\Attributes as OA;
use Prospecter\Core\Connectors\Application\Client\ConfigureClient\ConfigureClient;
use Symfony\Component\Clock\ClockInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[OA\Tag('Connectors')]
#[IsGranted('ROLE_ADMIN')]
final readonly class ConfigureGoogleClientController
{
    private const string GOOGLE = 'google';

    public function __construct(
        private CommandBus $commandBus,
        private ClockInterface $clock,
        private GoogleClientReader $googleClientReader,
        private JsonResponses $jsonResponses,
    ) {
    }

    #[Route('/api/me/organization/clients/google', name: 'api_me_configure_google_client', methods: ['PATCH'])]
    #[OA\Patch(summary: 'Configure my account\'s Google client, or replace it with another one. From then on, the members connect through it. Members connected through another client reconnect')]
    #[OA\Response(response: 200, description: 'My account\'s Google client', content: new Model(type: GoogleClientResource::class))]
    #[OA\Response(response: 401, description: 'Missing or invalid access token')]
    #[OA\Response(response: 403, description: 'The signed-in user is not the account\'s administrator', content: new Model(type: ProblemResource::class))]
    #[OA\Response(response: 422, description: 'The client has no id or no secret', content: new Model(type: ProblemResource::class))]
    #[OA\Response(response: 503, description: 'The client\'s secret cannot be stored or read right now (`secretsUnavailable`)', content: new Model(type: ProblemResource::class))]
    public function __invoke(#[MapRequestPayload] GoogleClientInput $input, #[CurrentUser] User $user): JsonResponse
    {
        $this->commandBus->dispatch(new ConfigureClient(
            memberId: $user->memberId(),
            provider: self::GOOGLE,
            clientId: $input->clientId ?? throw new \LogicException('A validated client has an id'),
            clientSecret: $input->clientSecret ?? throw new \LogicException('A validated client has a secret'),
            executedAt: $this->clock->now(),
        ));

        return $this->jsonResponses->of($this->googleClientReader->read($user));
    }
}
