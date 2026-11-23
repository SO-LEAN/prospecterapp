<?php

declare(strict_types=1);

namespace App\Connectors\Controller;

use App\Connectors\Google\GoogleAuthorization;
use App\Connectors\Resource\AuthorizationResource;
use App\Security\User;
use App\Shared\CommandBus;
use App\Shared\JsonResponses;
use App\Shared\Resource\ProblemResource;
use Nelmio\ApiDocBundle\Attribute\Model;
use OpenApi\Attributes as OA;
use Prospecter\Core\Connectors\Application\AccountClient;
use Prospecter\Core\Connectors\Application\AskForAuthorization\AskForAuthorization;
use Prospecter\Core\Connectors\Application\ConsultMyConnections\ConsultMyConnections;
use Prospecter\Core\Connectors\Application\ConsultMyConnections\KeptConnections;
use Prospecter\Core\SharedKernel\Application\QueryHandler;
use Symfony\Component\Clock\ClockInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[OA\Tag('Connectors')]
#[IsGranted('ROLE_USER')]
final readonly class AskForGoogleAuthorizationController
{
    private const string GOOGLE = 'google';

    public function __construct(
        private CommandBus $commandBus,
        private ClockInterface $clock,
        private GoogleAuthorization $googleAuthorization,
        private QueryHandler $consultMyConnections,
        private JsonResponses $jsonResponses,
    ) {
    }

    #[Route('/api/me/connections/google/authorizations', name: 'api_me_connections_google_authorize', methods: ['POST'])]
    #[OA\Post(summary: 'Ask Google for access to my calendar. The browser goes to the returned address. Google brings it back to the SPA with a code. The SPA gives the code to POST /api/me/connections/google within ten minutes')]
    #[OA\Response(response: 201, description: 'The requested authorization', content: new Model(type: AuthorizationResource::class))]
    #[OA\Response(response: 401, description: 'Missing or invalid access token')]
    #[OA\Response(response: 403, description: 'The signed-in user is not a user', content: new Model(type: ProblemResource::class))]
    #[OA\Response(response: 409, description: 'No Prospecter client at Google serves the account (`providerUnavailable`). The `connectGoogle` link is missing', content: new Model(type: ProblemResource::class))]
    #[OA\Response(response: 503, description: 'The client\'s secret cannot be stored or read right now (`secretsUnavailable`)', content: new Model(type: ProblemResource::class))]
    public function __invoke(#[CurrentUser] User $user): JsonResponse
    {
        $request = $this->googleAuthorization->newRequest();
        $this->commandBus->dispatch(new AskForAuthorization(memberId: $user->memberId(), provider: self::GOOGLE, state: $request->state, codeVerifier: $request->codeVerifier, executedAt: $this->clock->now()));

        return $this->jsonResponses->of(new AuthorizationResource($this->googleAuthorization->addressOf($request, $this->clientOfMyAccount($user))), Response::HTTP_CREATED);
    }

    /**
     * My account's Google client id. It comes with the providers offered to me (ADR 0125).
     */
    private function clientOfMyAccount(User $user): string
    {
        $kept = new KeptConnections();
        $this->consultMyConnections->__invoke(new ConsultMyConnections($user->memberId(), $this->clock->now()), $kept);
        $google = array_find($kept->offered, static fn (AccountClient $client): bool => self::GOOGLE === $client->provider);

        return $google->clientId ?? throw new \LogicException('The account\'s Google client was removed while the authorization was being requested');
    }
}
