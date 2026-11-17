<?php

namespace App\Campaigning\Controller;

use App\Campaigning\CampaignVoter;
use App\Campaigning\Resource\CampaignMessageResource;
use App\Security\User;
use App\Shared\Resource\ProblemResource;
use Nelmio\ApiDocBundle\Attribute\Model;
use OpenApi\Attributes as OA;
use Prospecter\Core\Campaigns\Application\CampaignSheet;
use Prospecter\Core\Campaigns\Application\RenderCampaignMessage\KeptCampaignMessage;
use Prospecter\Core\Campaigns\Application\RenderCampaignMessage\RenderCampaignMessage;
use Prospecter\Core\SharedKernel\Application\QueryHandler;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Requirement\Requirement;
use Symfony\Component\Security\Http\Attribute\CurrentUser;
use Symfony\Component\Security\Http\Attribute\IsGranted;

/**
 * The message of a campaign rendered by the core for one contact (ADR 0144), as every client reads it.
 */
#[OA\Tag('Campaigns')]
#[IsGranted('ROLE_PROSPECTOR')]
final class RenderCampaignMessageController extends AbstractController
{
    public function __construct(private readonly QueryHandler $renderCampaignMessage)
    {
    }

    #[Route('/api/campaigns/{id}/messages/{contactId}', name: 'api_campaigns_message', requirements: ['id' => Requirement::UUID, 'contactId' => Requirement::UUID], methods: ['GET'])]
    #[IsGranted(CampaignVoter::ACCESS, subject: 'campaign')]
    #[IsGranted(User::PERSONAL_DATA)]
    #[OA\Get(summary: 'The message of a campaign as I send or say it to one of my contacts')]
    #[OA\Response(response: 200, description: 'The message rendered for the contact', content: new Model(type: CampaignMessageResource::class))]
    #[OA\Response(response: 401, description: 'Missing or invalid access token')]
    #[OA\Response(response: 403, description: 'Not a prospector, or no right to manage personal data', content: new Model(type: ProblemResource::class))]
    #[OA\Response(response: 404, description: 'Unknown campaign or contact, or one of another account', content: new Model(type: ProblemResource::class))]
    public function __invoke(CampaignSheet $campaign, string $contactId, #[CurrentUser] User $user): JsonResponse
    {
        $kept = new KeptCampaignMessage();
        ($this->renderCampaignMessage)(new RenderCampaignMessage($user->memberId(), $campaign->id, $contactId), $kept);
        $message = $kept->message ?? throw new \LogicException('The message was not presented');

        return $this->json(new CampaignMessageResource($message->subject, $message->text));
    }
}
