<?php

declare(strict_types=1);

namespace App\Connectors\Controller;

use App\Connectors\GoogleClientReader;
use App\Connectors\Input\GmailNotificationsInput;
use App\Connectors\Resource\GoogleClientResource;
use App\Security\User;
use App\Shared\CommandBus;
use App\Shared\Resource\ProblemResource;
use Nelmio\ApiDocBundle\Attribute\Model;
use OpenApi\Attributes as OA;
use Prospecter\Core\Connectors\Application\ConfigureMailboxNotifications\ConfigureMailboxNotifications;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Clock\ClockInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[OA\Tag('Connectors')]
#[IsGranted('ROLE_ADMIN')]
final class ConfigureGmailNotificationsController extends AbstractController
{
    private const string GOOGLE = 'google';

    public function __construct(
        private readonly CommandBus $commandBus,
        private readonly ClockInterface $clock,
        private readonly GoogleClientReader $googleClientReader,
    ) {
    }

    #[Route('/api/me/organization/clients/google/gmail-notifications', name: 'api_me_configure_gmail_notifications', methods: ['PATCH'])]
    #[OA\Patch(summary: 'Name the topic in my account\'s Google Cloud that its members\' mailboxes notify Prospecter through. Also name the service account that pushes the notifications. From then on, the Gmail e-mail import is offered')]
    #[OA\Response(response: 200, description: 'My account\'s Google client', content: new Model(type: GoogleClientResource::class))]
    #[OA\Response(response: 401, description: 'Missing or invalid access token')]
    #[OA\Response(response: 403, description: 'The signed-in user is not the account\'s administrator', content: new Model(type: ProblemResource::class))]
    #[OA\Response(response: 404, description: 'My account has no Google client', content: new Model(type: ProblemResource::class))]
    #[OA\Response(response: 422, description: 'The topic is not named in full (`invalidTopic`), or the service account is not an address', content: new Model(type: ProblemResource::class))]
    #[OA\Response(response: 503, description: 'The client\'s secret cannot be stored or read right now (`secretsUnavailable`)', content: new Model(type: ProblemResource::class))]
    public function __invoke(#[MapRequestPayload] GmailNotificationsInput $input, #[CurrentUser] User $user): JsonResponse
    {
        $this->commandBus->dispatch(new ConfigureMailboxNotifications(
            memberId: $user->memberId(),
            provider: self::GOOGLE,
            topic: $input->topic ?? throw new \LogicException('A validated topic has a name'),
            pushAccount: $input->pushAccount ?? throw new \LogicException('A validated topic has a service account'),
            executedAt: $this->clock->now(),
        ));

        return $this->json($this->googleClientReader->read($user));
    }
}
