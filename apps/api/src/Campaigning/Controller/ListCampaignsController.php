<?php

namespace App\Campaigning\Controller;

use App\Campaigning\CampaignReader;
use App\Campaigning\Resource\CampaignListResource;
use App\Security\User;
use App\Shared\Resource\ProblemResource;
use Nelmio\ApiDocBundle\Attribute\Model;
use OpenApi\Attributes as OA;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[OA\Tag('Campaigns')]
#[IsGranted('ROLE_PROSPECTOR')]
final class ListCampaignsController extends AbstractController
{
    public function __construct(private readonly CampaignReader $campaignReader)
    {
    }

    #[Route('/api/campaigns', name: 'api_campaigns_list', methods: ['GET'])]
    #[IsGranted(User::PERSONAL_DATA)]
    #[OA\Get(summary: 'The campaigns of my team, the newest first, each with the contacts it targets counted')]
    #[OA\Response(response: 200, description: 'Every campaign of my team', content: new Model(type: CampaignListResource::class))]
    #[OA\Response(response: 401, description: 'Missing or invalid access token')]
    #[OA\Response(response: 403, description: 'Not a prospector, or no right to manage personal data', content: new Model(type: ProblemResource::class))]
    public function __invoke(#[CurrentUser] User $user): JsonResponse
    {
        return $this->json($this->campaignReader->list($user->memberId()));
    }
}
