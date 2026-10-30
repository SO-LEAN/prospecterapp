<?php

namespace App\SignIn\Controller;

use App\Security\User;
use App\SignIn\Resource\SignInResource;
use App\SignIn\SignInReader;
use Nelmio\ApiDocBundle\Attribute\Model;
use OpenApi\Attributes as OA;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[OA\Tag('Sign-in')]
#[IsGranted('ROLE_USER')]
final class ShowMySignInController extends AbstractController
{
    public function __construct(private readonly SignInReader $signInReader)
    {
    }

    #[Route('/api/me/sign-in', name: 'api_me_sign_in', methods: ['GET'])]
    #[OA\Get(summary: 'The email I sign in with')]
    #[OA\Response(response: 200, description: 'What I sign in with', content: new Model(type: SignInResource::class))]
    #[OA\Response(response: 401, description: 'Missing or invalid access token')]
    public function __invoke(#[CurrentUser] User $user): JsonResponse
    {
        return $this->json($this->signInReader->read($user->memberId()));
    }
}
