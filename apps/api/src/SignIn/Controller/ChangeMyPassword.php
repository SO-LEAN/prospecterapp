<?php

namespace App\SignIn\Controller;

use App\Security\User;
use App\Shared\Resource\ProblemResource;
use App\SignIn\Credentials;
use App\SignIn\Input\PasswordInput;
use Nelmio\ApiDocBundle\Attribute\Model;
use OpenApi\Attributes as OA;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[OA\Tag('Sign-in')]
#[IsGranted('ROLE_USER')]
final class ChangeMyPassword extends AbstractController
{
    public function __construct(private readonly Credentials $credentials)
    {
    }

    #[Route('/api/me/sign-in/password', name: 'api_me_change_password', methods: ['PATCH'])]
    #[OA\Patch(summary: 'Change my password')]
    #[OA\Response(response: 204, description: 'Password changed')]
    #[OA\Response(response: 401, description: 'Missing or invalid access token')]
    #[OA\Response(response: 422, description: 'Password too short', content: new Model(type: ProblemResource::class))]
    public function __invoke(#[MapRequestPayload] PasswordInput $input, #[CurrentUser] User $user): Response
    {
        $this->credentials->changePassword($user->memberId(), $input->password);

        return new Response(status: Response::HTTP_NO_CONTENT);
    }
}
