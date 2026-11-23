<?php

declare(strict_types=1);

namespace App\SignIn\Controller;

use App\Security\User;
use App\Shared\JsonResponses;
use App\Shared\Resource\ProblemResource;
use App\SignIn\Credentials;
use App\SignIn\Input\LoginEmailInput;
use App\SignIn\Resource\SignInResource;
use App\SignIn\SignInReader;
use Nelmio\ApiDocBundle\Attribute\Model;
use OpenApi\Attributes as OA;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[OA\Tag('Sign-in')]
#[IsGranted('ROLE_USER')]
final readonly class ChangeMyLoginEmailController
{
    public function __construct(private Credentials $credentials, private SignInReader $signInReader, private JsonResponses $jsonResponses)
    {
    }

    #[Route('/api/me/sign-in/login-email', name: 'api_me_change_login_email', methods: ['PATCH'])]
    #[OA\Patch(summary: 'Change the email I sign in with, unique on the platform')]
    #[OA\Response(response: 200, description: 'What I sign in with', content: new Model(type: SignInResource::class))]
    #[OA\Response(response: 401, description: 'Missing or invalid access token')]
    #[OA\Response(response: 422, description: 'Missing or invalid email, or an email that another member signs in with', content: new Model(type: ProblemResource::class))]
    public function __invoke(#[MapRequestPayload] LoginEmailInput $input, #[CurrentUser] User $user): JsonResponse
    {
        $this->credentials->changeLoginEmail($user->memberId(), $input->email);

        return $this->jsonResponses->of($this->signInReader->read($user->memberId()));
    }
}
