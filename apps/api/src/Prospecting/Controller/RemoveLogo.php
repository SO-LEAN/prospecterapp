<?php

namespace App\Prospecting\Controller;

use App\Prospecting\OrganizationReader;
use App\Prospecting\OrganizationVoter;
use App\Prospecting\Resource\OrganizationResource;
use App\Security\User;
use App\Shared\CommandBus;
use App\Shared\Resource\ProblemResource;
use Nelmio\ApiDocBundle\Attribute\Model;
use OpenApi\Attributes as OA;
use Prospecter\Core\Prospecting\Application\OrganizationResponse;
use Prospecter\Core\Prospecting\Application\RemoveLogo\RemoveLogo as RemoveLogoCommand;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Clock\ClockInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Requirement\Requirement;
use Symfony\Component\Security\Http\Attribute\CurrentUser;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[OA\Tag('Organizations')]
#[IsGranted('ROLE_PROSPECTOR')]
final class RemoveLogo extends AbstractController
{
    public function __construct(
        private readonly CommandBus $commands,
        private readonly ClockInterface $clock,
        private readonly OrganizationReader $organizations,
    ) {
    }

    #[Route('/api/organizations/{id}/logo', name: 'api_organizations_remove_logo', requirements: ['id' => Requirement::UUID], methods: ['DELETE'])]
    #[IsGranted(OrganizationVoter::REMOVE_LOGO, subject: 'organization')]
    #[OA\Delete(summary: 'Remove the logo of the organization')]
    #[OA\Response(response: 200, description: 'The organization without logo', content: new Model(type: OrganizationResource::class))]
    #[OA\Response(response: 401, description: 'Missing or invalid access token')]
    #[OA\Response(response: 403, description: 'The signed-in user is not a prospector', content: new Model(type: ProblemResource::class))]
    #[OA\Response(response: 409, description: 'The organization has no logo (no removeLogo link)', content: new Model(type: ProblemResource::class))]
    #[OA\Response(response: 404, description: 'Unknown organization, or one that is neither mine nor prospected by mine', content: new Model(type: ProblemResource::class))]
    public function __invoke(OrganizationResponse $organization, #[CurrentUser] User $user): JsonResponse
    {
        $this->commands->dispatch(new RemoveLogoCommand($user->memberId(), $organization->id, $this->clock->now()));

        return $this->json($this->organizations->read($organization->id));
    }
}
