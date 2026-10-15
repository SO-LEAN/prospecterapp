<?php

namespace App\Me\Controller;

use App\Entity\User;
use App\Me\MyAccountReader;
use App\Me\Resource\AccountResource;
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
final class ShowAccount extends AbstractController
{
    public function __construct(
        private readonly MyAccountReader $account,
    ) {
    }

    #[Route('/api/me', name: 'api_me_show', methods: ['GET'])]
    #[OA\Get(summary: 'Consult my account and my organization')]
    #[OA\Response(response: 200, description: 'My account', content: new Model(type: AccountResource::class))]
    #[OA\Response(response: 401, description: 'Missing or invalid access token')]
    #[OA\Response(response: 403, description: 'The signed-in user is not a user', content: new Model(type: ProblemResource::class))]
    public function __invoke(#[CurrentUser] User $user): JsonResponse
    {
        return $this->json($this->account->read($user->memberId()));
    }
}
