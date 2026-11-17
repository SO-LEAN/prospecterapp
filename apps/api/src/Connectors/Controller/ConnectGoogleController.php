<?php

namespace App\Connectors\Controller;

use App\Connectors\Input\GoogleAnswerInput;
use App\Connectors\MyConnectionsReader;
use App\Connectors\Resource\ConnectionsResource;
use App\Security\User;
use App\Shared\CommandBus;
use App\Shared\Resource\ProblemResource;
use Nelmio\ApiDocBundle\Attribute\Model;
use OpenApi\Attributes as OA;
use Prospecter\Core\Connectors\Application\Connect\Connect;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Clock\ClockInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[OA\Tag('Connectors')]
#[IsGranted('ROLE_USER')]
final class ConnectGoogleController extends AbstractController
{
    public function __construct(
        private readonly CommandBus $commandBus,
        private readonly ClockInterface $clock,
        private readonly MyConnectionsReader $myConnectionsReader,
    ) {
    }

    #[Route('/api/me/connections/google', name: 'api_me_connections_google_connect', methods: ['POST'])]
    #[OA\Post(summary: 'Connect my Google account with the code Google brought the SPA back with, replacing the account connected before')]
    #[OA\Response(response: 201, description: 'My connections, Google connected', content: new Model(type: ConnectionsResource::class))]
    #[OA\Response(response: 401, description: 'Missing or invalid access token')]
    #[OA\Response(response: 403, description: 'Google did not grant the access for this code', content: new Model(type: ProblemResource::class))]
    #[OA\Response(response: 404, description: 'No authorization of mine waits for this state: unknown, expired, or asked by another member', content: new Model(type: ProblemResource::class))]
    #[OA\Response(response: 422, description: 'The access to the calendar was not granted, or the answer is incomplete', content: new Model(type: ProblemResource::class))]
    #[OA\Response(response: 503, description: 'The secret of the client cannot be kept nor read for now (`secretsUnavailable`), or Google does not answer, fails or limits the requests: nothing is connected, the member goes through Google again (`googleUnreachable`)', content: new Model(type: ProblemResource::class))]
    public function __invoke(#[MapRequestPayload] GoogleAnswerInput $input, #[CurrentUser] User $user): JsonResponse
    {
        $this->commandBus->dispatch(new Connect(
            memberId: $user->memberId(),
            state: $input->state ?? throw new \LogicException('An answer validated has its state'),
            code: $input->code ?? throw new \LogicException('An answer validated has its code'),
            executedAt: $this->clock->now(),
        ));

        return $this->json($this->myConnectionsReader->read($user->memberId()), Response::HTTP_CREATED);
    }
}
