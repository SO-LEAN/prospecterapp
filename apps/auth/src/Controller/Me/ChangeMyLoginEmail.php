<?php

namespace App\Controller\Me;

use App\Input\LoginEmailInput;
use App\LoginEmails;
use App\Resource\MeResource;
use App\Resource\ProblemResource;
use App\Security\SignedInMember;
use Nelmio\ApiDocBundle\Attribute\Model;
use OpenApi\Attributes as OA;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;

#[OA\Tag('Sign-in')]
final class ChangeMyLoginEmail extends AbstractController
{
    public function __construct(private readonly LoginEmails $loginEmails)
    {
    }

    #[Route('/auth/me/login-email', name: 'auth_me_change_login_email', methods: ['PATCH'])]
    #[OA\Patch(summary: 'Change the email I sign in with, unique on the platform')]
    #[OA\Response(response: 200, description: 'What I sign in with', content: new Model(type: MeResource::class))]
    #[OA\Response(response: 401, description: 'Missing or invalid access token')]
    #[OA\Response(response: 422, description: 'Missing or invalid email, or one another member signs in with', content: new Model(type: ProblemResource::class))]
    public function __invoke(#[MapRequestPayload] LoginEmailInput $input, #[CurrentUser] SignedInMember $member): JsonResponse
    {
        $this->loginEmails->assign($member->getUserIdentifier(), $input->email);

        return $this->json(ShowMe::resourceOf($input->email, fn (string $route): string => $this->generateUrl($route)));
    }
}
