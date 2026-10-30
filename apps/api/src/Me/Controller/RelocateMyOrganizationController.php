<?php

namespace App\Me\Controller;

use App\Me\MyAccountReader;
use App\Me\Resource\AccountResource;
use App\Security\User;
use App\Shared\CommandBus;
use App\Shared\Input\AddressInput;
use App\Shared\Resource\ProblemResource;
use Nelmio\ApiDocBundle\Attribute\Model;
use OpenApi\Attributes as OA;
use Prospecter\Core\Accounts\Application\RelocateMyOrganization\RelocateMyOrganization;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Clock\ClockInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[OA\Tag('My account')]
#[IsGranted('ROLE_USER')]
final class RelocateMyOrganizationController extends AbstractController
{
    public function __construct(
        private readonly CommandBus $commandBus,
        private readonly ClockInterface $clock,
        private readonly MyAccountReader $myAccountReader,
    ) {
    }

    #[Route('/api/me/organization/address', name: 'api_me_relocate_organization', methods: ['PATCH'])]
    #[OA\Patch(summary: 'Move my organization to an address (located again), the start of the routes to the organizations; an empty address removes it')]
    #[OA\Response(response: 200, description: 'My account', content: new Model(type: AccountResource::class))]
    #[OA\Response(response: 401, description: 'Missing or invalid access token')]
    #[OA\Response(response: 403, description: 'The signed-in user is not a user', content: new Model(type: ProblemResource::class))]
    #[OA\Response(response: 422, description: 'Invalid address', content: new Model(type: ProblemResource::class))]
    public function __invoke(#[MapRequestPayload] AddressInput $input, #[CurrentUser] User $user): JsonResponse
    {
        $this->commandBus->dispatch(new RelocateMyOrganization($user->memberId(), $input->street, $input->postalCode, $input->city, $input->country, $this->clock->now()));

        return $this->json($this->myAccountReader->read($user->memberId()));
    }
}
