<?php

namespace App\Controller\Api\Account;

use App\Api\Input\UserNameInput;
use App\Api\Resource\AccountResource;
use App\Api\Resource\ProblemResource;
use App\Entity\User;
use App\Presenter\AccountResourcePresenter;
use Nelmio\ApiDocBundle\Attribute\Model;
use OpenApi\Attributes as OA;
use Prospecter\Core\Application\Account\ChangeMyUserNameRequest;
use Prospecter\Core\Application\UseCasesFacade;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;

#[OA\Tag('My account')]
final class ChangeMyUserName extends AbstractController
{
    public function __construct(
        private readonly UseCasesFacade $useCases,
        private readonly AccountResourcePresenter $presenter,
    ) {
    }

    #[Route('/api/me/user-name', name: 'api_me_change_user_name', methods: ['PATCH'])]
    #[OA\Patch(summary: 'Change my user name (the access token is bound to it: sign in again)')]
    #[OA\Response(response: 200, description: 'My account', content: new Model(type: AccountResource::class))]
    #[OA\Response(response: 401, description: 'Missing or invalid access token')]
    #[OA\Response(response: 422, description: 'Missing or invalid user name', content: new Model(type: ProblemResource::class))]
    public function __invoke(#[MapRequestPayload] UserNameInput $input, #[CurrentUser] User $user): JsonResponse
    {
        return $this->json($this->useCases->changeMyUserName(new ChangeMyUserNameRequest($input->userName), $this->presenter, $user));
    }
}
