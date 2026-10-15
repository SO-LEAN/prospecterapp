<?php

namespace App\Controller\Api\Account;

use App\Api\Input\LegalIdentityInput;
use App\Api\Resource\AccountResource;
use App\Api\Resource\ProblemResource;
use App\Entity\User;
use App\Presenter\AccountResourcePresenter;
use Nelmio\ApiDocBundle\Attribute\Model;
use OpenApi\Attributes as OA;
use Prospecter\Core\Application\Account\ReviseMyOrganizationIdentityRequest;
use Prospecter\Core\Application\UseCasesFacade;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;

#[OA\Tag('My account')]
final class ReviseMyOrganizationIdentity extends AbstractController
{
    public function __construct(
        private readonly UseCasesFacade $useCases,
        private readonly AccountResourcePresenter $presenter,
    ) {
    }

    #[Route('/api/me/organization/legal-identity', name: 'api_me_revise_organization_identity', methods: ['PATCH'])]
    #[OA\Patch(summary: 'Revise the legal identity of my organization')]
    #[OA\Response(response: 200, description: 'My account', content: new Model(type: AccountResource::class))]
    #[OA\Response(response: 401, description: 'Missing or invalid access token')]
    #[OA\Response(response: 422, description: 'Invalid identity', content: new Model(type: ProblemResource::class))]
    public function __invoke(#[MapRequestPayload] LegalIdentityInput $input, #[CurrentUser] User $user): JsonResponse
    {
        return $this->json($this->useCases->reviseMyOrganizationIdentity(new ReviseMyOrganizationIdentityRequest($input->toLegalIdentity()), $this->presenter, $user));
    }
}
