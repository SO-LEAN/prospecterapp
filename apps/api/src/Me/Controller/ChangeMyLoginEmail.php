<?php

namespace App\Me\Controller;

use App\Auth\LoginEmails;
use App\Entity\User;
use App\Me\Input\LoginEmailInput;
use App\Me\MyAccountReader;
use App\Me\Resource\AccountResource;
use App\Shared\Resource\ProblemResource;
use Nelmio\ApiDocBundle\Attribute\Model;
use OpenApi\Attributes as OA;
use Prospecter\Core\SharedKernel\Domain\Email;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[OA\Tag('My account')]
#[IsGranted('ROLE_USER')]
final class ChangeMyLoginEmail extends AbstractController
{
    public function __construct(
        private readonly LoginEmails $loginEmails,
        private readonly MyAccountReader $account,
    ) {
    }

    #[Route('/api/me/login-email', name: 'api_me_change_login_email', methods: ['PATCH'])]
    #[OA\Patch(summary: 'Change the email I sign in with, unique on the platform (the access token is bound to it: sign in again)')]
    #[OA\Response(response: 200, description: 'My account', content: new Model(type: AccountResource::class))]
    #[OA\Response(response: 401, description: 'Missing or invalid access token')]
    #[OA\Response(response: 403, description: 'The signed-in user is not a user', content: new Model(type: ProblemResource::class))]
    #[OA\Response(response: 422, description: 'Missing or invalid email, or one another member signs in with', content: new Model(type: ProblemResource::class))]
    public function __invoke(#[MapRequestPayload] LoginEmailInput $input, #[CurrentUser] User $user): JsonResponse
    {
        $this->loginEmails->assign($user->memberId(), new Email($input->email));

        return $this->json($this->account->read($user->memberId()));
    }
}
