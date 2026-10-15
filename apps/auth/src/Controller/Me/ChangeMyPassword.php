<?php

namespace App\Controller\Me;

use App\Input\PasswordInput;
use App\Passwords;
use App\Resource\ProblemResource;
use App\Security\SignedInMember;
use Nelmio\ApiDocBundle\Attribute\Model;
use OpenApi\Attributes as OA;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;

#[OA\Tag('Sign-in')]
final class ChangeMyPassword extends AbstractController
{
    public function __construct(private readonly Passwords $passwords)
    {
    }

    #[Route('/auth/me/password', name: 'auth_me_change_password', methods: ['PATCH'])]
    #[OA\Patch(summary: 'Change my password')]
    #[OA\Response(response: 204, description: 'Password changed')]
    #[OA\Response(response: 401, description: 'Missing or invalid access token')]
    #[OA\Response(response: 422, description: 'Password too short', content: new Model(type: ProblemResource::class))]
    public function __invoke(#[MapRequestPayload] PasswordInput $input, #[CurrentUser] SignedInMember $member): Response
    {
        $this->passwords->set($member->getUserIdentifier(), $input->password);

        return new Response(status: Response::HTTP_NO_CONTENT);
    }
}
