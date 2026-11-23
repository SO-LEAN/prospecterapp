<?php

declare(strict_types=1);

namespace App\Campaigning\Controller;

use App\Campaigning\CampaignVoter;
use App\Campaigning\Input\DismissalInput;
use App\Security\User;
use App\Shared\CommandBus;
use App\Shared\Resource\ProblemResource;
use Nelmio\ApiDocBundle\Attribute\Model;
use OpenApi\Attributes as OA;
use Prospecter\Core\Campaigns\Application\CampaignSheet;
use Prospecter\Core\Campaigns\Application\DismissRecipient\DismissRecipient;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Clock\ClockInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Requirement\Requirement;
use Symfony\Component\Security\Http\Attribute\CurrentUser;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[OA\Tag('Campaigns')]
#[IsGranted('ROLE_PROSPECTOR')]
final class DismissRecipientController extends AbstractController
{
    public function __construct(
        private readonly CommandBus $commandBus,
        private readonly ClockInterface $clock,
    ) {
    }

    #[Route('/api/campaigns/{id}/recipients/{contactId}/dismissal', name: 'api_campaigns_dismiss_recipient', requirements: ['id' => Requirement::UUID, 'contactId' => Requirement::UUID], methods: ['POST'])]
    #[IsGranted(CampaignVoter::ACCESS, subject: 'campaign')]
    #[IsGranted(User::PERSONAL_DATA)]
    #[OA\Post(summary: 'Dismiss a started campaign\'s recipient without an exchange. The campaign ends once nobody is left to handle')]
    #[OA\Response(response: 204, description: 'Dismissed')]
    #[OA\Response(response: 401, description: 'Missing or invalid access token')]
    #[OA\Response(response: 403, description: 'Not a prospector, or no right to manage personal data', content: new Model(type: ProblemResource::class))]
    #[OA\Response(response: 404, description: 'Unknown campaign or a campaign of another account, or not one of its recipients', content: new Model(type: ProblemResource::class))]
    #[OA\Response(response: 409, description: 'The campaign is not started, or the recipient is already handled', content: new Model(type: ProblemResource::class))]
    public function __invoke(CampaignSheet $campaign, string $contactId, #[MapRequestPayload] DismissalInput $input, #[CurrentUser] User $user): Response
    {
        $this->commandBus->dispatch(new DismissRecipient(memberId: $user->memberId(), campaignId: $campaign->id, contactId: $contactId, reason: $input->reason, executedAt: $this->clock->now()));

        return new Response(status: Response::HTTP_NO_CONTENT);
    }
}
