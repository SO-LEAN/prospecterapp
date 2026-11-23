<?php

declare(strict_types=1);

namespace App\Campaigning\Controller;

use App\Campaigning\CampaignReader;
use App\Campaigning\CampaignVoter;
use App\Campaigning\Input\RecipientSearchInput;
use App\Campaigning\Resource\CampaignResource;
use App\Security\User;
use App\Shared\CommandBus;
use App\Shared\Resource\ProblemResource;
use Nelmio\ApiDocBundle\Attribute\Model;
use OpenApi\Attributes as OA;
use Prospecter\Core\Campaigns\Application\AddRecipientsByTags\AddRecipientsByTags;
use Prospecter\Core\Campaigns\Application\CampaignSheet;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Clock\ClockInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Requirement\Requirement;
use Symfony\Component\Security\Http\Attribute\CurrentUser;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[OA\Tag('Campaigns')]
#[IsGranted('ROLE_PROSPECTOR')]
final class AddRecipientsByTagsController extends AbstractController
{
    public function __construct(
        private readonly CommandBus $commandBus,
        private readonly ClockInterface $clock,
        private readonly CampaignReader $campaignReader,
    ) {
    }

    #[Route('/api/campaigns/{id}/recipients/by-tags', name: 'api_campaigns_add_recipients_by_tags', requirements: ['id' => Requirement::UUID], methods: ['POST'])]
    #[IsGranted(CampaignVoter::ACCESS, subject: 'campaign')]
    #[IsGranted(User::PERSONAL_DATA)]
    #[OA\Post(summary: 'Add to the recipients of a frozen campaign the contacts carrying every tag chosen and that of its channel, those already kept kept once')]
    #[OA\Response(response: 200, description: 'The campaign, with its recipient count', content: new Model(type: CampaignResource::class))]
    #[OA\Response(response: 401, description: 'Missing or invalid access token')]
    #[OA\Response(response: 403, description: 'Not a prospector, or no right to manage personal data', content: new Model(type: ProblemResource::class))]
    #[OA\Response(response: 404, description: 'Unknown campaign, or a campaign of another account', content: new Model(type: ProblemResource::class))]
    #[OA\Response(response: 409, description: 'Not frozen', content: new Model(type: ProblemResource::class))]
    #[OA\Response(response: 422, description: 'No tag, more than five tags counting the channel\'s tag, or more than 500 recipients', content: new Model(type: ProblemResource::class))]
    public function __invoke(CampaignSheet $campaign, #[MapRequestPayload] RecipientSearchInput $input, #[CurrentUser] User $user): JsonResponse
    {
        $this->commandBus->dispatch(new AddRecipientsByTags(memberId: $user->memberId(), campaignId: $campaign->id, tags: $input->tags ?? [], executedAt: $this->clock->now()));

        return $this->json($this->campaignReader->read($user->memberId(), $campaign->id));
    }
}
