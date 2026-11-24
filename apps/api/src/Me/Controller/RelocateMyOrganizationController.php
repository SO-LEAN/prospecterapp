<?php

declare(strict_types=1);

namespace App\Me\Controller;

use App\Me\MyAccountReader;
use App\Me\Resource\AccountResource;
use App\Security\User;
use App\Shared\CommandBus;
use App\Shared\Input\AddressInput;
use App\Shared\JsonResponses;
use App\Shared\Resource\ProblemResource;
use Nelmio\ApiDocBundle\Attribute\Model;
use OpenApi\Attributes as OA;
use Prospecter\Core\Accounts\Application\Account\RelocateMyOrganization\RelocateMyOrganization;
use Symfony\Component\Clock\ClockInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[OA\Tag('My account')]
#[IsGranted('ROLE_USER')]
final readonly class RelocateMyOrganizationController
{
    public function __construct(
        private CommandBus $commandBus,
        private ClockInterface $clock,
        private MyAccountReader $myAccountReader,
        private JsonResponses $jsonResponses,
    ) {
    }

    #[Route('/api/me/organization/address', name: 'api_me_relocate_organization', methods: ['PATCH'])]
    #[OA\Patch(summary: 'Move my organization to an address and locate it again. The routes to the organizations start there. An empty address removes it.')]
    #[OA\Response(response: 200, description: 'My account', content: new Model(type: AccountResource::class))]
    #[OA\Response(response: 401, description: 'Missing or invalid access token')]
    #[OA\Response(response: 403, description: 'The signed-in user is not a user', content: new Model(type: ProblemResource::class))]
    #[OA\Response(response: 422, description: 'Invalid address', content: new Model(type: ProblemResource::class))]
    public function __invoke(#[MapRequestPayload] AddressInput $input, #[CurrentUser] User $user): JsonResponse
    {
        $this->commandBus->dispatch(new RelocateMyOrganization(memberId: $user->memberId(), street: $input->street, postalCode: $input->postalCode, city: $input->city, country: $input->country, executedAt: $this->clock->now()));

        return $this->jsonResponses->of($this->myAccountReader->read($user->memberId()));
    }
}
