<?php

declare(strict_types=1);

namespace App\Connectors\Controller;

use App\Connectors\Input\GoogleAnswerInput;
use App\Connectors\MyConnectionsReader;
use App\Connectors\Resource\ConnectionsResource;
use App\Security\User;
use App\Shared\CommandBus;
use App\Shared\JsonResponses;
use App\Shared\Resource\ProblemResource;
use Nelmio\ApiDocBundle\Attribute\Model;
use OpenApi\Attributes as OA;
use Prospecter\Core\Connectors\Application\Connect\Connect;
use Symfony\Component\Clock\ClockInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[OA\Tag('Connectors')]
#[IsGranted('ROLE_USER')]
final readonly class ConnectGoogleController
{
    public function __construct(
        private CommandBus $commandBus,
        private ClockInterface $clock,
        private MyConnectionsReader $myConnectionsReader,
        private JsonResponses $jsonResponses,
    ) {
    }

    #[Route('/api/me/connections/google', name: 'api_me_connections_google_connect', methods: ['POST'])]
    #[OA\Post(summary: 'Connect my Google account with the code that Google gave back to the SPA. It replaces the account connected before')]
    #[OA\Response(response: 201, description: 'My connections, with Google connected', content: new Model(type: ConnectionsResource::class))]
    #[OA\Response(response: 401, description: 'Missing or invalid access token')]
    #[OA\Response(response: 403, description: 'Google did not grant access for this code', content: new Model(type: ProblemResource::class))]
    #[OA\Response(response: 404, description: 'None of my authorizations waits for this state. It is unknown, expired, or requested by another member', content: new Model(type: ProblemResource::class))]
    #[OA\Response(response: 422, description: 'Access to the calendar was not granted, or the answer is incomplete', content: new Model(type: ProblemResource::class))]
    #[OA\Response(response: 503, description: 'The client\'s secret cannot be stored or read right now (`secretsUnavailable`). Or Google does not answer, fails or limits the requests (`googleUnreachable`). Nothing is connected, and the member goes through Google again', content: new Model(type: ProblemResource::class))]
    public function __invoke(#[MapRequestPayload] GoogleAnswerInput $input, #[CurrentUser] User $user): JsonResponse
    {
        $this->commandBus->dispatch(new Connect(
            memberId: $user->memberId(),
            state: $input->state ?? throw new \LogicException('A validated answer has a state'),
            code: $input->code ?? throw new \LogicException('A validated answer has a code'),
            executedAt: $this->clock->now(),
        ));

        return $this->jsonResponses->of($this->myConnectionsReader->read($user->memberId()), Response::HTTP_CREATED);
    }
}
