<?php

declare(strict_types=1);

namespace App\Connectors\Controller;

use App\Security\User;
use App\Shared\CommandBus;
use App\Shared\Resource\ProblemResource;
use Nelmio\ApiDocBundle\Attribute\Model;
use OpenApi\Attributes as OA;
use Prospecter\Core\Connectors\Application\CatchUpMailHistory\CatchUpMailHistory;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Clock\ClockInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Requirement\Requirement;
use Symfony\Component\Security\Http\Attribute\CurrentUser;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[OA\Tag('Connectors')]
#[IsGranted('ROLE_USER')]
final class ReimportContactGmailController extends AbstractController
{
    public function __construct(
        private readonly CommandBus $commandBus,
        private readonly ClockInterface $clock,
    ) {
    }

    #[Route('/api/contacts/{id}/gmail-reimports', name: 'api_contacts_reimport_gmail', requirements: ['id' => Requirement::UUID], methods: ['POST'])]
    #[IsGranted(User::PERSONAL_DATA)]
    #[OA\Post(summary: 'Reimport the e-mails that my Gmail mailbox exchanged with the contact over the past 12 months, the 200 newest. They are imported asynchronously, by the same rules as my e-mail import')]
    #[OA\Response(response: 202, description: 'The reimport started. The e-mails reach the contact\'s timeline')]
    #[OA\Response(response: 401, description: 'Missing or invalid access token')]
    #[OA\Response(response: 403, description: 'The signed-in user is not allowed to manage personal data', content: new Model(type: ProblemResource::class))]
    #[OA\Response(response: 404, description: 'No connection to Google, an unknown contact, or another account\'s contact', content: new Model(type: ProblemResource::class))]
    #[OA\Response(response: 409, description: 'My e-mail import is not active, or the contact has no e-mail', content: new Model(type: ProblemResource::class))]
    public function __invoke(string $id, #[CurrentUser] User $user): Response
    {
        $this->commandBus->dispatch(CatchUpMailHistory::ofContact($user->memberId(), $id, $this->clock->now()));

        return new Response(status: Response::HTTP_ACCEPTED);
    }
}
