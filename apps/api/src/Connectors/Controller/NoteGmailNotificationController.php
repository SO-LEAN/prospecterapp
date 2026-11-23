<?php

declare(strict_types=1);

namespace App\Connectors\Controller;

use App\Connectors\Input\PubSubPushInput;
use App\Shared\CommandBus;
use Nelmio\ApiDocBundle\Attribute\Model;
use OpenApi\Attributes as OA;
use Prospecter\Core\Connectors\Application\NoteMailboxNotification\NoteMailboxNotification;
use Symfony\Component\Clock\ClockInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Requirement\Requirement;

#[OA\Tag('Connectors')]
final readonly class NoteGmailNotificationController
{
    private const string GOOGLE = 'google';

    public function __construct(
        private CommandBus $commandBus,
        private ClockInterface $clock,
    ) {
    }

    #[Route('/api/accounts/{account}/gmail-notifications', name: 'api_gmail_notifications', requirements: ['account' => Requirement::UUID], methods: ['POST'])]
    #[OA\Post(summary: 'Record a change that a Gmail mailbox notified through the account\'s topic. The topic\'s Pub/Sub subscription pushes it with a token that Google signed for the account\'s service account, not with a member\'s token (ADR 0154). The change is recorded and dated. Nothing is read from Gmail during the request', security: [])]
    #[OA\RequestBody(content: new Model(type: PubSubPushInput::class))]
    #[OA\Response(response: 204, description: 'Recorded, or ignored when no member of the account imports the mailbox')]
    #[OA\Response(response: 400, description: 'The message\'s data is not a Gmail notification')]
    #[OA\Response(response: 422, description: 'The message has no data')]
    #[OA\Response(response: 401, description: 'The token is missing, not signed by Google, not issued for this endpoint, or not issued for the account\'s service account. Or the account has no Gmail topic')]
    public function __invoke(string $account, #[MapRequestPayload] PubSubPushInput $input): Response
    {
        $notification = $input->notification();
        $this->commandBus->dispatch(new NoteMailboxNotification(accountId: $account, provider: self::GOOGLE, mailbox: $notification['emailAddress'], historyId: $notification['historyId'], executedAt: $this->clock->now()));

        return new Response(status: Response::HTTP_NO_CONTENT);
    }
}
