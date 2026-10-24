<?php

namespace App\Exchanges;

use App\Exchanges\Resource\AttachmentDownloadResource;
use App\Security\User;
use App\Shared\Resource\ProblemResource;
use Nelmio\ApiDocBundle\Attribute\Model;
use OpenApi\Attributes as OA;
use Prospecter\Core\Prospecting\Application\ConsultExchange\ExchangeDetailResponse;
use Prospecter\Core\Prospecting\Application\DownloadAttachment\DownloadAttachment as DownloadAttachmentQuery;
use Prospecter\Core\SharedKernel\Application\RefusalTranslatingHandler;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Requirement\Requirement;
use Symfony\Component\Security\Http\Attribute\CurrentUser;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[OA\Tag('Contacts')]
#[IsGranted('ROLE_PROSPECTOR')]
final class DownloadAttachment extends AbstractController
{
    public function __construct(private readonly RefusalTranslatingHandler $downloadAttachment)
    {
    }

    #[Route('/api/exchanges/{id}/attachments/{attachmentId}', name: 'api_exchanges_download_attachment', requirements: ['id' => Requirement::UUID, 'attachmentId' => Requirement::UUID], methods: ['GET'])]
    #[IsGranted(ExchangeVoter::DOWNLOAD_ATTACHMENT, subject: 'exchange')]
    #[OA\Get(summary: 'Where to read a file joined to an exchange, for a minute')]
    #[OA\Response(response: 200, description: 'A link signed for a minute', content: new Model(type: AttachmentDownloadResource::class))]
    #[OA\Response(response: 401, description: 'Missing or invalid access token')]
    #[OA\Response(response: 403, description: 'Not a prospector, or no right to manage personal data', content: new Model(type: ProblemResource::class))]
    #[OA\Response(response: 404, description: 'Unknown exchange or attachment, or one of another account', content: new Model(type: ProblemResource::class))]
    public function __invoke(ExchangeDetailResponse $exchange, string $attachmentId, #[CurrentUser] User $user): JsonResponse
    {
        $download = ($this->downloadAttachment)(new DownloadAttachmentQuery($user->memberId(), $exchange->id, $attachmentId));

        return $this->json(new AttachmentDownloadResource($download->name, $download->url));
    }
}
