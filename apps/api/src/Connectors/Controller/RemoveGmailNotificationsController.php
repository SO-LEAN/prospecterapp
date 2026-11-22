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
use Prospecter\Core\Connectors\Application\RemoveMailboxNotifications\RemoveMailboxNotifications;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Clock\ClockInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[OA\Tag('Connectors')]
#[IsGranted('ROLE_ADMIN')]
final class RemoveGmailNotificationsController extends AbstractController
{
    private const string GOOGLE = 'google';

    public function __construct(
        private readonly CommandBus $commandBus,
        private readonly ClockInterface $clock,
        private readonly GoogleClientReader $googleClientReader,
    ) {
    }

    #[Route('/api/me/organization/clients/google/gmail-notifications', name: 'api_me_remove_gmail_notifications', methods: ['DELETE'])]
    #[OA\Delete(summary: 'Remove the topic of my account: the import of the e-mails of Gmail is no longer offered, the watches of the mailboxes expiring within their week')]
    #[OA\Response(response: 200, description: 'The client of Google of my account, without its topic', content: new Model(type: GoogleClientResource::class))]
    #[OA\Response(response: 401, description: 'Missing or invalid access token')]
    #[OA\Response(response: 403, description: 'The signed-in user is no administrator of the account', content: new Model(type: ProblemResource::class))]
    #[OA\Response(response: 404, description: 'My account has no client of Google', content: new Model(type: ProblemResource::class))]
    #[OA\Response(response: 503, description: 'The secret of the client cannot be kept nor read for now (`secretsUnavailable`)', content: new Model(type: ProblemResource::class))]
    public function __invoke(#[CurrentUser] User $user): JsonResponse
    {
        $this->commandBus->dispatch(new RemoveMailboxNotifications($user->memberId(), self::GOOGLE, $this->clock->now()));

        return $this->json($this->googleClientReader->read($user));
    }
}
