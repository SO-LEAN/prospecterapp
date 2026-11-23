<?php

declare(strict_types=1);

namespace App\Campaigning\Controller;

use App\Campaigning\CampaignVoter;
use App\Campaigning\RecipientsReader;
use App\Campaigning\Resource\RecipientListResource;
use App\Security\User;
use App\Shared\Resource\ProblemResource;
use Nelmio\ApiDocBundle\Attribute\Model;
use OpenApi\Attributes as OA;
use Prospecter\Core\Campaigns\Application\CampaignSheet;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Requirement\Requirement;
use Symfony\Component\Security\Http\Attribute\CurrentUser;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[OA\Tag('Campaigns')]
#[IsGranted('ROLE_PROSPECTOR')]
final class ShowCampaignRecipientsController extends AbstractController
{
    public function __construct(private readonly RecipientsReader $recipientsReader)
    {
    }

    #[Route('/api/campaigns/{id}/recipients', name: 'api_campaigns_recipients', requirements: ['id' => Requirement::UUID], methods: ['GET'])]
    #[IsGranted(CampaignVoter::ACCESS, subject: 'campaign')]
    #[IsGranted(User::PERSONAL_DATA)]
    #[OA\Get(summary: 'The recipients a campaign keeps, in the order they were added, none in a draft')]
    #[OA\Response(response: 200, description: 'Every recipient', content: new Model(type: RecipientListResource::class))]
    #[OA\Response(response: 401, description: 'Missing or invalid access token')]
    #[OA\Response(response: 403, description: 'Not a prospector, or no right to manage personal data', content: new Model(type: ProblemResource::class))]
    #[OA\Response(response: 404, description: 'Unknown campaign, or a campaign of another account', content: new Model(type: ProblemResource::class))]
    public function __invoke(CampaignSheet $campaign, #[CurrentUser] User $user): JsonResponse
    {
        return $this->json($this->recipientsReader->read($user->memberId(), $campaign));
    }
}
