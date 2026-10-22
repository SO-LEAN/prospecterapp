<?php

namespace App\Prospecting\Controller;

use App\Prospecting\Input\TimelineQuery;
use App\Prospecting\Presenter\TimelinePresenter;
use App\Prospecting\Resource\TimelineResource;
use App\Security\User;
use App\Shared\Resource\ProblemResource;
use Nelmio\ApiDocBundle\Attribute\Model;
use OpenApi\Attributes as OA;
use Prospecter\Core\Prospecting\Application\ConsultTimeline\ConsultTimeline;
use Prospecter\Core\SharedKernel\Application\RefusalTranslatingHandler;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Attribute\MapQueryString;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Requirement\Requirement;
use Symfony\Component\Security\Http\Attribute\CurrentUser;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[OA\Tag('Contacts')]
#[IsGranted('ROLE_PROSPECTOR')]
final class ShowTimeline extends AbstractController
{
    public function __construct(
        private readonly RefusalTranslatingHandler $consultTimeline,
        private readonly TimelinePresenter $presenter,
    ) {
    }

    #[Route('/api/contacts/{id}/exchanges', name: 'api_contacts_timeline', requirements: ['id' => Requirement::UUID], methods: ['GET'])]
    #[OA\Get(summary: 'The exchanges with the contact, the most recent first')]
    #[OA\Response(response: 200, description: 'A slice of the exchanges, the next one at the link next', content: new Model(type: TimelineResource::class))]
    #[OA\Response(response: 401, description: 'Missing or invalid access token')]
    #[OA\Response(response: 403, description: 'Not a prospector', content: new Model(type: ProblemResource::class))]
    #[OA\Response(response: 404, description: 'Unknown contact, or one of another account', content: new Model(type: ProblemResource::class))]
    public function __invoke(string $id, #[CurrentUser] User $user, #[MapQueryString] TimelineQuery $query = new TimelineQuery()): JsonResponse
    {
        return $this->json($this->presenter->present($id, ($this->consultTimeline)(new ConsultTimeline($user->memberId(), $id, $query->after))));
    }
}
