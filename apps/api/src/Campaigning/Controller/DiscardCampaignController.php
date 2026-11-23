<?php

declare(strict_types=1);

namespace App\Campaigning\Controller;

use App\Campaigning\CampaignVoter;
use App\Security\User;
use App\Shared\CommandBus;
use App\Shared\Resource\ProblemResource;
use Nelmio\ApiDocBundle\Attribute\Model;
use OpenApi\Attributes as OA;
use Prospecter\Core\Campaigns\Application\CampaignSheet;
use Prospecter\Core\Campaigns\Application\DiscardCampaign\DiscardCampaign;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Clock\ClockInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Requirement\Requirement;
use Symfony\Component\Security\Http\Attribute\CurrentUser;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[OA\Tag('Campaigns')]
#[IsGranted('ROLE_PROSPECTOR')]
final class DiscardCampaignController extends AbstractController
{
    public function __construct(
        private readonly CommandBus $commandBus,
        private readonly ClockInterface $clock,
    ) {
    }

    #[Route('/api/campaigns/{id}', name: 'api_campaigns_discard', requirements: ['id' => Requirement::UUID], methods: ['DELETE'])]
    #[IsGranted(CampaignVoter::ACCESS, subject: 'campaign')]
    #[IsGranted(User::PERSONAL_DATA)]
    #[OA\Delete(summary: 'Discard a campaign never started')]
    #[OA\Response(response: 204, description: 'Discarded')]
    #[OA\Response(response: 401, description: 'Missing or invalid access token')]
    #[OA\Response(response: 403, description: 'Not a prospector, or no right to manage personal data', content: new Model(type: ProblemResource::class))]
    #[OA\Response(response: 404, description: 'Unknown campaign, or a campaign of another account', content: new Model(type: ProblemResource::class))]
    #[OA\Response(response: 409, description: 'Started: a started campaign is stopped, never discarded', content: new Model(type: ProblemResource::class))]
    public function __invoke(CampaignSheet $campaign, #[CurrentUser] User $user): Response
    {
        $this->commandBus->dispatch(new DiscardCampaign($user->memberId(), $campaign->id, $this->clock->now()));

        return new Response(status: Response::HTTP_NO_CONTENT);
    }
}
