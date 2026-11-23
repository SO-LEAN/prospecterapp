<?php

declare(strict_types=1);

namespace App\Campaigning\Controller;

use App\Campaigning\CampaignReader;
use App\Campaigning\CampaignVoter;
use App\Campaigning\Resource\CampaignResource;
use App\Security\User;
use App\Shared\CommandBus;
use App\Shared\JsonResponses;
use App\Shared\Resource\ProblemResource;
use Nelmio\ApiDocBundle\Attribute\Model;
use OpenApi\Attributes as OA;
use Prospecter\Core\Campaigns\Application\CampaignSheet;
use Prospecter\Core\Campaigns\Application\StartCampaign\StartCampaign;
use Symfony\Component\Clock\ClockInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Requirement\Requirement;
use Symfony\Component\Security\Http\Attribute\CurrentUser;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[OA\Tag('Campaigns')]
#[IsGranted('ROLE_PROSPECTOR')]
final readonly class StartCampaignController
{
    public function __construct(
        private CommandBus $commandBus,
        private ClockInterface $clock,
        private CampaignReader $campaignReader,
        private JsonResponses $jsonResponses,
    ) {
    }

    #[Route('/api/campaigns/{id}/start', name: 'api_campaigns_start', requirements: ['id' => Requirement::UUID], methods: ['POST'])]
    #[IsGranted(CampaignVoter::ACCESS, subject: 'campaign')]
    #[IsGranted(User::PERSONAL_DATA)]
    #[OA\Post(summary: 'Start a frozen campaign. Its recipients, message and channel no longer change. From then on it is stopped, never discarded')]
    #[OA\Response(response: 200, description: 'The started campaign', content: new Model(type: CampaignResource::class))]
    #[OA\Response(response: 401, description: 'Missing or invalid access token')]
    #[OA\Response(response: 403, description: 'Not a prospector, or no right to manage personal data', content: new Model(type: ProblemResource::class))]
    #[OA\Response(response: 404, description: 'Unknown campaign, or a campaign of another account', content: new Model(type: ProblemResource::class))]
    #[OA\Response(response: 409, description: 'Not frozen: a draft, or already started', content: new Model(type: ProblemResource::class))]
    #[OA\Response(response: 422, description: 'Frozen, but empty because its contacts were erased', content: new Model(type: ProblemResource::class))]
    public function __invoke(CampaignSheet $campaign, #[CurrentUser] User $user): JsonResponse
    {
        $this->commandBus->dispatch(new StartCampaign($user->memberId(), $campaign->id, $this->clock->now()));

        return $this->jsonResponses->of($this->campaignReader->read($user->memberId(), $campaign->id));
    }
}
