<?php

declare(strict_types=1);

namespace App\Campaigning\Controller;

use App\Campaigning\CampaignComposition;
use App\Campaigning\Input\CampaignInput;
use App\Campaigning\Resource\CampaignResource;
use App\Security\User;
use App\Shared\CommandBus;
use App\Shared\Identities;
use App\Shared\JsonResponses;
use App\Shared\Resource\ProblemResource;
use Nelmio\ApiDocBundle\Attribute\Model;
use OpenApi\Attributes as OA;
use Symfony\Component\Clock\ClockInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[OA\Tag('Campaigns')]
#[IsGranted('ROLE_PROSPECTOR')]
final readonly class PrepareCampaignController
{
    public function __construct(
        private CommandBus $commandBus,
        private Identities $identities,
        private ClockInterface $clock,
        private CampaignComposition $campaigns,
        private JsonResponses $jsonResponses,
    ) {
    }

    #[Route('/api/campaigns', name: 'api_campaigns_prepare', methods: ['POST'])]
    #[IsGranted(User::PERSONAL_DATA)]
    #[OA\Post(summary: 'Prepare a team campaign as a draft')]
    #[OA\Response(response: 201, description: 'The prepared draft', content: new Model(type: CampaignResource::class))]
    #[OA\Response(response: 401, description: 'Missing or invalid access token')]
    #[OA\Response(response: 403, description: 'Not a prospector, or no right to manage personal data', content: new Model(type: ProblemResource::class))]
    #[OA\Response(response: 422, description: 'No name or a name too long, an unknown channel, no tag or more than five tags counting the channel\'s tag, a message too long, or a subject for a channel other than e-mail', content: new Model(type: ProblemResource::class))]
    public function __invoke(#[MapRequestPayload] CampaignInput $input, #[CurrentUser] User $user): JsonResponse
    {
        $id = $this->identities->next();
        $this->commandBus->dispatch($input->preparationOf($user->memberId(), $id, $this->clock->now()));

        return $this->jsonResponses->of($this->campaigns->read($user->memberId(), $id), Response::HTTP_CREATED);
    }
}
