<?php

namespace App\Prospecting\Controller;

use App\Prospecting\Presenter\MeetingPlacePresenter;
use App\Prospecting\Resource\MeetingPlaceResource;
use App\Security\User;
use App\Shared\Resource\ProblemResource;
use Nelmio\ApiDocBundle\Attribute\Model;
use OpenApi\Attributes as OA;
use Prospecter\Core\Prospecting\Application\ProposeMeetingPlace\ProposeMeetingPlace;
use Prospecter\Core\SharedKernel\Application\QueryHandler;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Requirement\Requirement;
use Symfony\Component\Security\Http\Attribute\CurrentUser;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[OA\Tag('Contacts')]
#[IsGranted('ROLE_PROSPECTOR')]
final class ProposeMeetingPlaceController extends AbstractController
{
    public function __construct(
        private readonly QueryHandler $proposeMeetingPlace,
    ) {
    }

    #[Route('/api/contacts/{id}/meeting-place', name: 'api_contacts_meeting_place', requirements: ['id' => Requirement::UUID], methods: ['GET'])]
    #[OA\Get(summary: 'Where to meet the contact, proposed while planning a meeting with them: the address of their organization')]
    #[OA\Response(response: 200, description: 'The place proposed, or none', content: new Model(type: MeetingPlaceResource::class))]
    #[OA\Response(response: 401, description: 'Missing or invalid access token')]
    #[OA\Response(response: 403, description: 'Not a prospector', content: new Model(type: ProblemResource::class))]
    #[OA\Response(response: 404, description: 'Unknown contact, or one of another account', content: new Model(type: ProblemResource::class))]
    public function __invoke(string $id, #[CurrentUser] User $user): JsonResponse
    {
        $presenter = new MeetingPlacePresenter();
        ($this->proposeMeetingPlace)(new ProposeMeetingPlace($user->memberId(), $id), $presenter);

        return $this->json($presenter->resource);
    }
}
