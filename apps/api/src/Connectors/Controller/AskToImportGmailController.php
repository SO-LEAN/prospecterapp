<?php

declare(strict_types=1);

namespace App\Connectors\Controller;

use App\Connectors\Google\GoogleAuthorization;
use App\Connectors\Resource\AuthorizationResource;
use App\Security\User;
use App\Shared\CommandBus;
use App\Shared\Resource\ProblemResource;
use Nelmio\ApiDocBundle\Attribute\Model;
use OpenApi\Attributes as OA;
use Prospecter\Core\Connectors\Application\AccountClient;
use Prospecter\Core\Connectors\Application\AskToImportMail\AskToImportMail;
use Prospecter\Core\Connectors\Application\ConsultMyConnections\ConsultMyConnections;
use Prospecter\Core\Connectors\Application\ConsultMyConnections\KeptConnections;
use Prospecter\Core\Connectors\Application\MyConnection;
use Prospecter\Core\SharedKernel\Application\QueryHandler;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Clock\ClockInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[OA\Tag('Connectors')]
#[IsGranted(User::PERSONAL_DATA)]
final class AskToImportGmailController extends AbstractController
{
    private const string GOOGLE = 'google';

    public function __construct(
        private readonly CommandBus $commandBus,
        private readonly ClockInterface $clock,
        private readonly GoogleAuthorization $googleAuthorization,
        private readonly QueryHandler $consultMyConnections,
    ) {
    }

    #[Route('/api/me/connections/google/gmail-import/authorizations', name: 'api_me_connections_google_import_gmail', methods: ['POST'])]
    #[OA\Post(summary: 'Ask Google to read my mailbox along with my calendar, to import my Gmail e-mails (ADR 0154). The browser goes to the returned address. Google brings it back to the SPA with a code. The SPA gives the code to POST /api/me/connections/google within ten minutes')]
    #[OA\Response(response: 201, description: 'The requested authorization', content: new Model(type: AuthorizationResource::class))]
    #[OA\Response(response: 401, description: 'Missing or invalid access token')]
    #[OA\Response(response: 403, description: 'The signed-in user is not allowed to manage personal data', content: new Model(type: ProblemResource::class))]
    #[OA\Response(response: 409, description: 'No Prospecter client at Google serves the account (`providerUnavailable`), or the client has no Gmail topic (`mailImportUnavailable`). The `importGmail` link is missing', content: new Model(type: ProblemResource::class))]
    #[OA\Response(response: 503, description: 'The client\'s secret cannot be stored or read right now (`secretsUnavailable`)', content: new Model(type: ProblemResource::class))]
    public function __invoke(#[CurrentUser] User $user): JsonResponse
    {
        $request = $this->googleAuthorization->newRequest();
        $this->commandBus->dispatch(new AskToImportMail(memberId: $user->memberId(), provider: self::GOOGLE, state: $request->state, codeVerifier: $request->codeVerifier, executedAt: $this->clock->now()));

        $mine = $this->myConnections($user);

        return $this->json(new AuthorizationResource($this->googleAuthorization->addressImportingGmailOf($request, self::clientOfMyAccount($mine), self::accountConnectedIn($mine))), Response::HTTP_CREATED);
    }

    private function myConnections(User $user): KeptConnections
    {
        $kept = new KeptConnections();
        $this->consultMyConnections->__invoke(new ConsultMyConnections($user->memberId(), $this->clock->now()), $kept);

        return $kept;
    }

    /**
     * My account's Google client id. It comes with the providers offered to me (ADR 0125).
     */
    private static function clientOfMyAccount(KeptConnections $mine): string
    {
        $google = array_find($mine->offered, static fn (AccountClient $client): bool => self::GOOGLE === $client->provider);

        return $google->clientId ?? throw new \LogicException('The account\'s Google client was removed while the authorization was being requested');
    }

    /**
     * The account I connected at Google. Google asks this account to grant access to its mailbox, whatever account
     * the browser signed in to first.
     */
    private static function accountConnectedIn(KeptConnections $mine): ?string
    {
        return array_find($mine->connections, static fn (MyConnection $connection): bool => self::GOOGLE === $connection->provider)?->account;
    }
}
