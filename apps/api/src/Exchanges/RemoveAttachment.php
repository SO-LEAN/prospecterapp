<?php

namespace App\Exchanges;

use App\Exchanges\Resource\ExchangeDetailResource;
use App\Security\User;
use App\Shared\CommandBus;
use App\Shared\Resource\ProblemResource;
use Nelmio\ApiDocBundle\Attribute\Model;
use OpenApi\Attributes as OA;
use Prospecter\Core\Prospecting\Application\ConsultExchange\ExchangeDetailResponse;
use Prospecter\Core\Prospecting\Application\RemoveAttachment\RemoveAttachment as RemoveAttachmentCommand;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Clock\ClockInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Requirement\Requirement;
use Symfony\Component\Security\Http\Attribute\CurrentUser;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[OA\Tag('Contacts')]
#[IsGranted('ROLE_PROSPECTOR')]
final class RemoveAttachment extends AbstractController
{
    public function __construct(
        private readonly CommandBus $commands,
        private readonly ClockInterface $clock,
        private readonly ExchangeReader $exchanges,
    ) {
    }

    #[Route('/api/exchanges/{id}/attachments/{attachmentId}', name: 'api_exchanges_remove_attachment', requirements: ['id' => Requirement::UUID, 'attachmentId' => Requirement::UUID], methods: ['DELETE'])]
    #[IsGranted(ExchangeVoter::ACCESS, subject: 'exchange')]
    #[IsGranted(User::PERSONAL_DATA)]
    #[OA\Delete(summary: 'Remove a file joined to an exchange, and forget it')]
    #[OA\Response(response: 200, description: 'The exchange without the file', content: new Model(type: ExchangeDetailResource::class))]
    #[OA\Response(response: 401, description: 'Missing or invalid access token')]
    #[OA\Response(response: 403, description: 'Not a prospector, or no right to manage personal data', content: new Model(type: ProblemResource::class))]
    #[OA\Response(response: 404, description: 'Unknown exchange or attachment, or one of another account', content: new Model(type: ProblemResource::class))]
    public function __invoke(ExchangeDetailResponse $exchange, string $attachmentId, #[CurrentUser] User $user): JsonResponse
    {
        $this->commands->dispatch(new RemoveAttachmentCommand($user->memberId(), $exchange->id, $attachmentId, $this->clock->now()));

        return $this->json($this->exchanges->read($user->memberId(), $exchange->id));
    }
}
