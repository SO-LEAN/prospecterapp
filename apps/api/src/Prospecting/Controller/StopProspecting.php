<?php

namespace App\Prospecting\Controller;

use App\Security\User;
use App\Shared\CommandBus;
use App\Shared\Resource\ProblemResource;
use Nelmio\ApiDocBundle\Attribute\Model;
use OpenApi\Attributes as OA;
use Prospecter\Core\Prospecting\Application\StopProspecting\StopProspecting as StopProspectingCommand;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Clock\ClockInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Requirement\Requirement;
use Symfony\Component\Security\Http\Attribute\CurrentUser;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[OA\Tag('Organizations')]
#[IsGranted('ROLE_PROSPECTOR')]
final class StopProspecting extends AbstractController
{
    public function __construct(private readonly CommandBus $commands, private readonly ClockInterface $clock)
    {
    }

    #[Route('/api/organizations/{id}', name: 'api_organizations_stop_prospecting', requirements: ['id' => Requirement::UUID], methods: ['DELETE'])]
    #[OA\Delete(summary: 'Stop prospecting the organization: its subsidiaries lose their holding, its contacts that no other organization names are erased')]
    #[OA\Response(response: 204, description: 'No longer prospected')]
    #[OA\Response(response: 401, description: 'Missing or invalid access token')]
    #[OA\Response(response: 403, description: 'Not a prospector, or no right to manage personal data while the organization has contacts', content: new Model(type: ProblemResource::class))]
    #[OA\Response(response: 404, description: 'Unknown organization, or one of another account', content: new Model(type: ProblemResource::class))]
    public function __invoke(string $id, #[CurrentUser] User $user): Response
    {
        $this->commands->dispatch(new StopProspectingCommand($user->memberId(), $id, $this->clock->now()));

        return new Response(status: Response::HTTP_NO_CONTENT);
    }
}
