<?php

namespace App\Me\Controller;

use App\Entity\User;
use App\Me\Input\PersonNameInput;
use App\Me\MyAccountReader;
use App\Me\Resource\AccountResource;
use App\Shared\CommandBus;
use App\Shared\Resource\ProblemResource;
use Nelmio\ApiDocBundle\Attribute\Model;
use OpenApi\Attributes as OA;
use Prospecter\Core\PersonalData\Application\RenamePerson\RenamePerson;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[OA\Tag('My account')]
#[IsGranted('ROLE_USER')]
final class ChangeMyName extends AbstractController
{
    public function __construct(
        private readonly CommandBus $commands,
        private readonly MyAccountReader $account,
    ) {
    }

    #[Route('/api/me/name', name: 'api_me_change_name', methods: ['PATCH'])]
    #[OA\Patch(summary: 'Change my first and last names, nothing else changes')]
    #[OA\Response(response: 200, description: 'My account', content: new Model(type: AccountResource::class))]
    #[OA\Response(response: 401, description: 'Missing or invalid access token')]
    #[OA\Response(response: 403, description: 'The signed-in user is not a user', content: new Model(type: ProblemResource::class))]
    #[OA\Response(response: 422, description: 'Invalid name', content: new Model(type: ProblemResource::class))]
    public function __invoke(#[MapRequestPayload] PersonNameInput $input, #[CurrentUser] User $user): JsonResponse
    {
        $this->commands->dispatch(new RenamePerson($user->memberId(), $user->personId(), $input->toPersonName()));

        return $this->json($this->account->read($user->memberId()));
    }
}
