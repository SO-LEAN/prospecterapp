<?php

declare(strict_types=1);

namespace App\SignIn\Controller;

use App\Security\User;
use App\Shared\JsonResponses;
use App\SignIn\Resource\SignInResource;
use App\SignIn\SignInReader;
use Nelmio\ApiDocBundle\Attribute\Model;
use OpenApi\Attributes as OA;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[OA\Tag('Sign-in')]
#[IsGranted('ROLE_USER')]
final readonly class ShowMySignInController
{
    public function __construct(private SignInReader $signInReader, private JsonResponses $jsonResponses)
    {
    }

    #[Route('/api/me/sign-in', name: 'api_me_sign_in', methods: ['GET'])]
    #[OA\Get(summary: 'Get the email I sign in with')]
    #[OA\Response(response: 200, description: 'What I sign in with', content: new Model(type: SignInResource::class))]
    #[OA\Response(response: 401, description: 'Missing or invalid access token')]
    public function __invoke(#[CurrentUser] User $user): JsonResponse
    {
        return $this->jsonResponses->of($this->signInReader->read($user->memberId()));
    }
}
