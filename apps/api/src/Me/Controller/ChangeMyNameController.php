<?php

declare(strict_types=1);

namespace App\Me\Controller;

use App\Me\MyAccountReader;
use App\Me\Resource\AccountResource;
use App\PersonalData\Input\PersonNameInput;
use App\Security\User;
use App\Shared\CommandBus;
use App\Shared\JsonResponses;
use App\Shared\Resource\ProblemResource;
use Nelmio\ApiDocBundle\Attribute\Model;
use OpenApi\Attributes as OA;
use Prospecter\Core\PersonalData\Application\RenamePerson\RenamePerson;
use Symfony\Component\Clock\ClockInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[OA\Tag('My account')]
#[IsGranted('ROLE_USER')]
final readonly class ChangeMyNameController
{
    public function __construct(
        private CommandBus $commandBus,
        private ClockInterface $clock,
        private MyAccountReader $myAccountReader,
        private JsonResponses $jsonResponses,
    ) {
    }

    #[Route('/api/me/name', name: 'api_me_change_name', methods: ['PATCH'])]
    #[OA\Patch(summary: 'Change my first and last names. Nothing else changes.')]
    #[OA\Response(response: 200, description: 'My account', content: new Model(type: AccountResource::class))]
    #[OA\Response(response: 401, description: 'Missing or invalid access token')]
    #[OA\Response(response: 403, description: 'The signed-in user is not a user', content: new Model(type: ProblemResource::class))]
    #[OA\Response(response: 422, description: 'Invalid name', content: new Model(type: ProblemResource::class))]
    public function __invoke(#[MapRequestPayload] PersonNameInput $input, #[CurrentUser] User $user): JsonResponse
    {
        $this->commandBus->dispatch(new RenamePerson(readerId: $user->memberId(), id: $user->personId(), firstName: $input->firstName, lastName: $input->lastName, executedAt: $this->clock->now()));

        return $this->jsonResponses->of($this->myAccountReader->read($user->memberId()));
    }
}
