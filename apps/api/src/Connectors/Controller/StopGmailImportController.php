<?php

declare(strict_types=1);

namespace App\Connectors\Controller;

use App\Connectors\MyConnectionsReader;
use App\Connectors\Resource\ConnectionsResource;
use App\Security\User;
use App\Shared\CommandBus;
use App\Shared\JsonResponses;
use App\Shared\Resource\ProblemResource;
use Nelmio\ApiDocBundle\Attribute\Model;
use OpenApi\Attributes as OA;
use Prospecter\Core\Connectors\Application\MailImport\StopMailImport\StopMailImport;
use Symfony\Component\Clock\ClockInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[OA\Tag('Connectors')]
#[IsGranted('ROLE_USER')]
final readonly class StopGmailImportController
{
    private const string GOOGLE = 'google';

    public function __construct(
        private CommandBus $commandBus,
        private ClockInterface $clock,
        private MyConnectionsReader $myConnectionsReader,
        private JsonResponses $jsonResponses,
    ) {
    }

    #[Route('/api/me/connections/google/gmail-import', name: 'api_me_connections_google_stop_gmail_import', methods: ['DELETE'])]
    #[OA\Delete(summary: 'Stop importing my Gmail e-mails. My mailbox no longer notifies Prospecter. The exchanges already imported stay, and my calendar stays connected (ADR 0154)')]
    #[OA\Response(response: 200, description: 'My connections, with the Gmail import off', content: new Model(type: ConnectionsResource::class))]
    #[OA\Response(response: 401, description: 'Missing or invalid access token')]
    #[OA\Response(response: 403, description: 'The signed-in user is not a user', content: new Model(type: ProblemResource::class))]
    #[OA\Response(response: 404, description: 'No connection to Google', content: new Model(type: ProblemResource::class))]
    public function __invoke(#[CurrentUser] User $user): JsonResponse
    {
        $this->commandBus->dispatch(new StopMailImport($user->memberId(), self::GOOGLE, $this->clock->now()));

        return $this->jsonResponses->of($this->myConnectionsReader->read($user->memberId()));
    }
}
