<?php

declare(strict_types=1);

namespace App\Campaigning\Controller;

use App\Campaigning\CampaignReader;
use App\Campaigning\CampaignVoter;
use App\Campaigning\Input\RecipientInput;
use App\Campaigning\Resource\CampaignResource;
use App\Security\User;
use App\Shared\CommandBus;
use App\Shared\JsonResponses;
use App\Shared\Resource\ProblemResource;
use Nelmio\ApiDocBundle\Attribute\Model;
use OpenApi\Attributes as OA;
use Prospecter\Core\Campaigns\Application\AddRecipient\AddRecipient;
use Prospecter\Core\Campaigns\Application\CampaignSheet;
use Symfony\Component\Clock\ClockInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Requirement\Requirement;
use Symfony\Component\Security\Http\Attribute\CurrentUser;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[OA\Tag('Campaigns')]
#[IsGranted('ROLE_PROSPECTOR')]
final readonly class AddRecipientController
{
    public function __construct(
        private CommandBus $commandBus,
        private ClockInterface $clock,
        private CampaignReader $campaignReader,
        private JsonResponses $jsonResponses,
    ) {
    }

    #[Route('/api/campaigns/{id}/recipients', name: 'api_campaigns_add_recipient', requirements: ['id' => Requirement::UUID], methods: ['POST'])]
    #[IsGranted(CampaignVoter::ACCESS, subject: 'campaign')]
    #[IsGranted(User::PERSONAL_DATA)]
    #[OA\Post(summary: 'Add a chosen contact to a frozen campaign\'s recipients. A contact is kept once')]
    #[OA\Response(response: 200, description: 'The campaign, with its recipient count', content: new Model(type: CampaignResource::class))]
    #[OA\Response(response: 401, description: 'Missing or invalid access token')]
    #[OA\Response(response: 403, description: 'Not a prospector, or no right to manage personal data', content: new Model(type: ProblemResource::class))]
    #[OA\Response(response: 404, description: 'Unknown campaign or a campaign of another account, or not a contact of my organization', content: new Model(type: ProblemResource::class))]
    #[OA\Response(response: 409, description: 'Not frozen', content: new Model(type: ProblemResource::class))]
    #[OA\Response(response: 422, description: 'A contact without the means the channel needs, or more than 500 recipients', content: new Model(type: ProblemResource::class))]
    public function __invoke(CampaignSheet $campaign, #[MapRequestPayload] RecipientInput $input, #[CurrentUser] User $user): JsonResponse
    {
        $this->commandBus->dispatch(new AddRecipient(memberId: $user->memberId(), campaignId: $campaign->id, contactId: (string) $input->contactId, executedAt: $this->clock->now()));

        return $this->jsonResponses->of($this->campaignReader->read($user->memberId(), $campaign->id));
    }
}
