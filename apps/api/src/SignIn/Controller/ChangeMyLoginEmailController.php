<?php

namespace App\SignIn\Controller;

use App\Security\User;
use App\Shared\Resource\ProblemResource;
use App\SignIn\Credentials;
use App\SignIn\Input\LoginEmailInput;
use App\SignIn\Resource\SignInResource;
use App\SignIn\SignInReader;
use Nelmio\ApiDocBundle\Attribute\Model;
use OpenApi\Attributes as OA;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[OA\Tag('Sign-in')]
#[IsGranted('ROLE_USER')]
final class ChangeMyLoginEmailController extends AbstractController
{
    public function __construct(private readonly Credentials $credentials, private readonly SignInReader $signInReader)
    {
    }

    #[Route('/api/me/sign-in/login-email', name: 'api_me_change_login_email', methods: ['PATCH'])]
    #[OA\Patch(summary: 'Change the email I sign in with, unique on the platform')]
    #[OA\Response(response: 200, description: 'What I sign in with', content: new Model(type: SignInResource::class))]
    #[OA\Response(response: 401, description: 'Missing or invalid access token')]
    #[OA\Response(response: 422, description: 'Missing or invalid email, or one another member signs in with', content: new Model(type: ProblemResource::class))]
    public function __invoke(#[MapRequestPayload] LoginEmailInput $input, #[CurrentUser] User $user): JsonResponse
    {
        $this->credentials->changeLoginEmail($user->memberId(), $input->email);

        return $this->json($this->signInReader->read($user->memberId()));
    }
}
