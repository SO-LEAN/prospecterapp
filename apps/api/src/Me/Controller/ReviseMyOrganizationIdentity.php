<?php

namespace App\Me\Controller;

use App\Me\MyAccountReader;
use App\Me\Resource\AccountResource;
use App\Security\User;
use App\Shared\CommandBus;
use App\Shared\Input\LegalIdentityInput;
use App\Shared\Resource\ProblemResource;
use Nelmio\ApiDocBundle\Attribute\Model;
use OpenApi\Attributes as OA;
use Prospecter\Core\Accounts\Application\ReviseMyOrganizationIdentity\ReviseMyOrganizationIdentity as ReviseMyOrganizationIdentityCommand;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Clock\ClockInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[OA\Tag('My account')]
#[IsGranted('ROLE_USER')]
final class ReviseMyOrganizationIdentity extends AbstractController
{
    public function __construct(
        private readonly CommandBus $commands,
        private readonly ClockInterface $clock,
        private readonly MyAccountReader $account,
    ) {
    }

    #[Route('/api/me/organization/legal-identity', name: 'api_me_revise_organization_identity', methods: ['PATCH'])]
    #[OA\Patch(summary: 'Revise the legal identity of my organization')]
    #[OA\Response(response: 200, description: 'My account', content: new Model(type: AccountResource::class))]
    #[OA\Response(response: 401, description: 'Missing or invalid access token')]
    #[OA\Response(response: 403, description: 'The signed-in user is not a user', content: new Model(type: ProblemResource::class))]
    #[OA\Response(response: 422, description: 'Invalid identity', content: new Model(type: ProblemResource::class))]
    public function __invoke(#[MapRequestPayload] LegalIdentityInput $input, #[CurrentUser] User $user): JsonResponse
    {
        $this->commands->dispatch(new ReviseMyOrganizationIdentityCommand($user->memberId(), $input->corporateName, $input->form, $input->type, $this->clock->now()));

        return $this->json($this->account->read($user->memberId()));
    }
}
