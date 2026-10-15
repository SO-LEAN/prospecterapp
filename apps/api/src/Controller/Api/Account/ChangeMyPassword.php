<?php

namespace App\Controller\Api\Account;

use App\Api\Input\PasswordInput;
use App\Api\Resource\ProblemResource;
use App\Auth\Passwords;
use App\Entity\User;
use Nelmio\ApiDocBundle\Attribute\Model;
use OpenApi\Attributes as OA;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[OA\Tag('My account')]
#[IsGranted('ROLE_USER')]
final class ChangeMyPassword extends AbstractController
{
    public function __construct(private readonly Passwords $passwords)
    {
    }

    #[Route('/api/me/password', name: 'api_me_change_password', methods: ['PATCH'])]
    #[OA\Patch(summary: 'Change my password (a credential of the authentication module, not of the core)')]
    #[OA\Response(response: 204, description: 'Password changed')]
    #[OA\Response(response: 401, description: 'Missing or invalid access token')]
    #[OA\Response(response: 403, description: 'The signed-in user is not a user', content: new Model(type: ProblemResource::class))]
    #[OA\Response(response: 422, description: 'Password too short', content: new Model(type: ProblemResource::class))]
    public function __invoke(#[MapRequestPayload] PasswordInput $input, #[CurrentUser] User $user): Response
    {
        $this->passwords->set($user->getUserId(), $input->password);

        return new Response(status: Response::HTTP_NO_CONTENT);
    }
}
