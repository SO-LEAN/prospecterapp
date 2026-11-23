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
use Prospecter\Core\Campaigns\Application\RemoveRecipient\RemoveRecipient;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Clock\ClockInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Requirement\Requirement;
use Symfony\Component\Security\Http\Attribute\CurrentUser;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[OA\Tag('Campaigns')]
#[IsGranted('ROLE_PROSPECTOR')]
final class RemoveRecipientController extends AbstractController
{
    public function __construct(
        private readonly CommandBus $commandBus,
        private readonly ClockInterface $clock,
    ) {
    }

    #[Route('/api/campaigns/{id}/recipients/{contactId}', name: 'api_campaigns_remove_recipient', requirements: ['id' => Requirement::UUID, 'contactId' => Requirement::UUID], methods: ['DELETE'])]
    #[IsGranted(CampaignVoter::ACCESS, subject: 'campaign')]
    #[IsGranted(User::PERSONAL_DATA)]
    #[OA\Delete(summary: 'Take a recipient off a frozen campaign, never the last one')]
    #[OA\Response(response: 204, description: 'Removed')]
    #[OA\Response(response: 401, description: 'Missing or invalid access token')]
    #[OA\Response(response: 403, description: 'Not a prospector, or no right to manage personal data', content: new Model(type: ProblemResource::class))]
    #[OA\Response(response: 404, description: 'Unknown campaign or a campaign of another account, or not one of its recipients', content: new Model(type: ProblemResource::class))]
    #[OA\Response(response: 409, description: 'Not frozen', content: new Model(type: ProblemResource::class))]
    #[OA\Response(response: 422, description: 'The campaign\'s last recipient', content: new Model(type: ProblemResource::class))]
    public function __invoke(CampaignSheet $campaign, string $contactId, #[CurrentUser] User $user): Response
    {
        $this->commandBus->dispatch(new RemoveRecipient(memberId: $user->memberId(), campaignId: $campaign->id, contactId: $contactId, executedAt: $this->clock->now()));

        return new Response(status: Response::HTTP_NO_CONTENT);
    }
}
