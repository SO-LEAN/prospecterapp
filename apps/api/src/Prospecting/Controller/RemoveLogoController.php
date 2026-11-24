<?php

declare(strict_types=1);

namespace App\Prospecting\Controller;

use App\Prospecting\OrganizationReader;
use App\Prospecting\OrganizationVoter;
use App\Prospecting\Resource\OrganizationResource;
use App\Security\User;
use App\Shared\CommandBus;
use App\Shared\JsonResponses;
use App\Shared\Resource\ProblemResource;
use Nelmio\ApiDocBundle\Attribute\Model;
use OpenApi\Attributes as OA;
use Prospecter\Core\Prospecting\Application\Organization\RemoveLogo\RemoveLogo;
use Prospecter\Core\Prospecting\Application\OrganizationSheet;
use Symfony\Component\Clock\ClockInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Requirement\Requirement;
use Symfony\Component\Security\Http\Attribute\CurrentUser;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[OA\Tag('Organizations')]
#[IsGranted('ROLE_PROSPECTOR')]
final readonly class RemoveLogoController
{
    public function __construct(
        private CommandBus $commandBus,
        private ClockInterface $clock,
        private OrganizationReader $organizationReader,
        private JsonResponses $jsonResponses,
    ) {
    }

    #[Route('/api/organizations/{id}/logo', name: 'api_organizations_remove_logo', requirements: ['id' => Requirement::UUID], methods: ['DELETE'])]
    #[IsGranted(OrganizationVoter::ACCESS, subject: 'organization')]
    #[OA\Delete(summary: 'Remove the organization\'s logo')]
    #[OA\Response(response: 200, description: 'The organization without its logo', content: new Model(type: OrganizationResource::class))]
    #[OA\Response(response: 401, description: 'Missing or invalid access token')]
    #[OA\Response(response: 403, description: 'The signed-in user is not a prospector', content: new Model(type: ProblemResource::class))]
    #[OA\Response(response: 409, description: 'The organization has no logo (no removeLogo link)', content: new Model(type: ProblemResource::class))]
    #[OA\Response(response: 404, description: 'Unknown organization, or one that my account neither owns nor prospects', content: new Model(type: ProblemResource::class))]
    public function __invoke(OrganizationSheet $organization, #[CurrentUser] User $user): JsonResponse
    {
        $this->commandBus->dispatch(new RemoveLogo($user->memberId(), $organization->id, $this->clock->now()));

        return $this->jsonResponses->of($this->organizationReader->read($organization->id));
    }
}
