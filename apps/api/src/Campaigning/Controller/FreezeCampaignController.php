<?php

declare(strict_types=1);

namespace App\Campaigning\Controller;

use App\Campaigning\CampaignComposition;
use App\Campaigning\CampaignVoter;
use App\Campaigning\Resource\CampaignResource;
use App\Security\User;
use App\Shared\CommandBus;
use App\Shared\JsonResponses;
use App\Shared\Resource\ProblemResource;
use Nelmio\ApiDocBundle\Attribute\Model;
use OpenApi\Attributes as OA;
use Prospecter\Core\Campaigns\Application\Campaign\FreezeCampaign\FreezeCampaign;
use Prospecter\Core\Campaigns\Application\CampaignSheet;
use Symfony\Component\Clock\ClockInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Requirement\Requirement;
use Symfony\Component\Security\Http\Attribute\CurrentUser;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[OA\Tag('Campaigns')]
#[IsGranted('ROLE_PROSPECTOR')]
final readonly class FreezeCampaignController
{
    public function __construct(
        private CommandBus $commandBus,
        private ClockInterface $clock,
        private CampaignComposition $campaigns,
        private JsonResponses $jsonResponses,
    ) {
    }

    #[Route('/api/campaigns/{id}/freeze', name: 'api_campaigns_freeze', requirements: ['id' => Requirement::UUID], methods: ['POST'])]
    #[IsGranted(CampaignVoter::ACCESS, subject: 'campaign')]
    #[IsGranted(User::PERSONAL_DATA)]
    #[OA\Post(summary: 'Freeze a draft campaign: the contacts it targets now become its recipients for good, and its channel and tags no longer change')]
    #[OA\Response(response: 200, description: 'The frozen campaign', content: new Model(type: CampaignResource::class))]
    #[OA\Response(response: 401, description: 'Missing or invalid access token')]
    #[OA\Response(response: 403, description: 'Not a prospector, or no right to manage personal data', content: new Model(type: ProblemResource::class))]
    #[OA\Response(response: 404, description: 'Unknown campaign, or a campaign of another account', content: new Model(type: ProblemResource::class))]
    #[OA\Response(response: 409, description: 'No longer a draft', content: new Model(type: ProblemResource::class))]
    #[OA\Response(response: 422, description: 'Nobody targeted, or more than 500 contacts', content: new Model(type: ProblemResource::class))]
    public function __invoke(CampaignSheet $campaign, #[CurrentUser] User $user): JsonResponse
    {
        $this->commandBus->dispatch(new FreezeCampaign($user->memberId(), $campaign->id, $this->clock->now()));

        return $this->jsonResponses->of($this->campaigns->read($user->memberId(), $campaign->id));
    }
}
