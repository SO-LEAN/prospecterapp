<?php

namespace App\Connectors\Controller;

use App\Connectors\Input\PubSubPushInput;
use App\Shared\CommandBus;
use Nelmio\ApiDocBundle\Attribute\Model;
use OpenApi\Attributes as OA;
use Prospecter\Core\Connectors\Application\NoteMailboxNotification\NoteMailboxNotification;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Clock\ClockInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Requirement\Requirement;

#[OA\Tag('Connectors')]
final class NoteGmailNotificationController extends AbstractController
{
    private const string GOOGLE = 'google';

    public function __construct(
        private readonly CommandBus $commandBus,
        private readonly ClockInterface $clock,
    ) {
    }

    #[Route('/api/accounts/{account}/gmail-notifications', name: 'api_gmail_notifications', requirements: ['account' => Requirement::UUID], methods: ['POST'])]
    #[OA\Post(summary: 'A change of a mailbox Gmail notified through the topic of the account, pushed by its subscription at Pub/Sub with a token Google signed for its account of service, not with a token of a member (ADR 0154): noted and dated, nothing read from Gmail during the request', security: [])]
    #[OA\RequestBody(content: new Model(type: PubSubPushInput::class))]
    #[OA\Response(response: 204, description: 'Noted, or ignored for a mailbox no member of the account imports')]
    #[OA\Response(response: 400, description: 'A message whose data is no notification of Gmail')]
    #[OA\Response(response: 422, description: 'A message without data')]
    #[OA\Response(response: 401, description: 'A token missing, not signed by Google, not issued for this endpoint, not for the account of service of the account, or an account without topic of Gmail')]
    public function __invoke(string $account, #[MapRequestPayload] PubSubPushInput $input): Response
    {
        $notification = $input->notification();
        $this->commandBus->dispatch(new NoteMailboxNotification($account, self::GOOGLE, $notification['emailAddress'], $notification['historyId'], $this->clock->now()));

        return new Response(status: Response::HTTP_NO_CONTENT);
    }
}
