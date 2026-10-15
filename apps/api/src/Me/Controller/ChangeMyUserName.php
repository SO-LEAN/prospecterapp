<?php

namespace App\Me\Controller;

use App\Entity\User;
use App\Me\Input\UserNameInput;
use App\Me\MyAccountReader;
use App\Me\Resource\AccountResource;
use App\Shared\CommandBus;
use App\Shared\Resource\ProblemResource;
use Nelmio\ApiDocBundle\Attribute\Model;
use OpenApi\Attributes as OA;
use Prospecter\Core\Accounts\Application\ChangeMyUserName\ChangeMyUserName as ChangeMyUserNameCommand;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[OA\Tag('My account')]
#[IsGranted('ROLE_USER')]
final class ChangeMyUserName extends AbstractController
{
    public function __construct(
        private readonly CommandBus $commands,
        private readonly MyAccountReader $account,
    ) {
    }

    #[Route('/api/me/user-name', name: 'api_me_change_user_name', methods: ['PATCH'])]
    #[OA\Patch(summary: 'Change my user name (the access token is bound to it: sign in again)')]
    #[OA\Response(response: 200, description: 'My account', content: new Model(type: AccountResource::class))]
    #[OA\Response(response: 401, description: 'Missing or invalid access token')]
    #[OA\Response(response: 403, description: 'The signed-in user is not a user', content: new Model(type: ProblemResource::class))]
    #[OA\Response(response: 422, description: 'Missing or invalid user name', content: new Model(type: ProblemResource::class))]
    public function __invoke(#[MapRequestPayload] UserNameInput $input, #[CurrentUser] User $user): JsonResponse
    {
        $this->commands->dispatch(new ChangeMyUserNameCommand($user->memberId(), $input->userName));

        return $this->json($this->account->read($user->memberId()));
    }
}
