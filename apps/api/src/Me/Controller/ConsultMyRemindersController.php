<?php

namespace App\Me\Controller;

use App\Me\MyRemindersReader;
use App\Me\Resource\RemindersResource;
use App\Security\User;
use App\Shared\Resource\ProblemResource;
use Nelmio\ApiDocBundle\Attribute\Model;
use OpenApi\Attributes as OA;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[OA\Tag('My account')]
#[IsGranted('ROLE_USER')]
final class ConsultMyRemindersController extends AbstractController
{
    public function __construct(
        private readonly MyRemindersReader $myRemindersReader,
    ) {
    }

    #[Route('/api/me/reminders', name: 'api_me_reminders', methods: ['GET'])]
    #[OA\Get(summary: 'Consult whether and how long before a planned exchange I am reminded of it, ten minutes before by default')]
    #[OA\Response(response: 200, description: 'My reminders', content: new Model(type: RemindersResource::class))]
    #[OA\Response(response: 401, description: 'Missing or invalid access token')]
    #[OA\Response(response: 403, description: 'The signed-in user is not a user', content: new Model(type: ProblemResource::class))]
    public function __invoke(#[CurrentUser] User $user): JsonResponse
    {
        return $this->json($this->myRemindersReader->read($user->memberId()));
    }
}
