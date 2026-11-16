<?php

namespace App\Connectors\Controller;

use App\Connectors\Google\GoogleAuthorization;
use App\Connectors\Resource\AuthorizationResource;
use App\Security\User;
use App\Shared\CommandBus;
use App\Shared\Resource\ProblemResource;
use Nelmio\ApiDocBundle\Attribute\Model;
use OpenApi\Attributes as OA;
use Prospecter\Core\Connectors\Application\AccountClient;
use Prospecter\Core\Connectors\Application\AskForAuthorization\AskForAuthorization;
use Prospecter\Core\Connectors\Application\ConsultMyConnections\ConsultMyConnections;
use Prospecter\Core\Connectors\Application\ConsultMyConnections\KeptConnections;
use Prospecter\Core\SharedKernel\Application\QueryHandler;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Clock\ClockInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[OA\Tag('Connectors')]
#[IsGranted('ROLE_USER')]
final class AskForGoogleAuthorizationController extends AbstractController
{
    private const string GOOGLE = 'google';

    public function __construct(
        private readonly CommandBus $commandBus,
        private readonly ClockInterface $clock,
        private readonly GoogleAuthorization $googleAuthorization,
        private readonly QueryHandler $consultMyConnections,
    ) {
    }

    #[Route('/api/me/connections/google/authorizations', name: 'api_me_connections_google_authorize', methods: ['POST'])]
    #[OA\Post(summary: 'Ask Google for the access to my calendar: the browser goes to the address answered, Google brings it back to the SPA with a code to give to POST /api/me/connections/google within ten minutes')]
    #[OA\Response(response: 201, description: 'The authorization asked', content: new Model(type: AuthorizationResource::class))]
    #[OA\Response(response: 401, description: 'Missing or invalid access token')]
    #[OA\Response(response: 403, description: 'The signed-in user is not a user', content: new Model(type: ProblemResource::class))]
    #[OA\Response(response: 409, description: 'No client of Prospecter at Google serves the account (`providerUnavailable`): the link `connectGoogle` is missing', content: new Model(type: ProblemResource::class))]
    #[OA\Response(response: 503, description: 'The secret of the client cannot be kept nor read for now (`secretsUnavailable`)', content: new Model(type: ProblemResource::class))]
    public function __invoke(#[CurrentUser] User $user): JsonResponse
    {
        $request = $this->googleAuthorization->newRequest();
        $this->commandBus->dispatch(new AskForAuthorization($user->memberId(), self::GOOGLE, $request->state, $request->codeVerifier, $this->clock->now()));

        return $this->json(new AuthorizationResource($this->googleAuthorization->addressOf($request, $this->clientOfMyAccount($user))), Response::HTTP_CREATED);
    }

    /**
     * The id of the client of Google of my account, read with the providers offered to me (ADR 0125).
     */
    private function clientOfMyAccount(User $user): string
    {
        $kept = new KeptConnections();
        ($this->consultMyConnections)(new ConsultMyConnections($user->memberId(), $this->clock->now()), $kept);
        $google = array_find($kept->offered, static fn (AccountClient $client): bool => self::GOOGLE === $client->provider);

        return $google->clientId ?? throw new \LogicException('The client of Google of the account was removed while the authorization was asked');
    }
}
