<?php

namespace App\Controller\Api\Account;

use App\Api\Input\ContactDetailsInput;
use App\Api\Resource\AccountResource;
use App\Api\Resource\ProblemResource;
use App\Entity\User;
use App\Presenter\AccountResourcePresenter;
use Nelmio\ApiDocBundle\Attribute\Model;
use OpenApi\Attributes as OA;
use Prospecter\Core\Application\Account\ChangeMyContactDetails\ChangeMyContactDetailsRequest;
use Prospecter\Core\Application\UseCasesFacade;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[OA\Tag('My account')]
#[IsGranted('ROLE_USER')]
final class ChangeMyContactDetails extends AbstractController
{
    public function __construct(
        private readonly UseCasesFacade $useCases,
        private readonly AccountResourcePresenter $presenter,
    ) {
    }

    #[Route('/api/me/contact-details', name: 'api_me_change_contact_details', methods: ['PATCH'])]
    #[OA\Patch(summary: 'Change my email, phone number and language, nothing else changes')]
    #[OA\Response(response: 200, description: 'My account', content: new Model(type: AccountResource::class))]
    #[OA\Response(response: 401, description: 'Missing or invalid access token')]
    #[OA\Response(response: 403, description: 'The signed-in user is not a user', content: new Model(type: ProblemResource::class))]
    #[OA\Response(response: 422, description: 'Invalid contact details or email already used', content: new Model(type: ProblemResource::class))]
    public function __invoke(#[MapRequestPayload] ContactDetailsInput $input, #[CurrentUser] User $user): JsonResponse
    {
        return $this->json($this->useCases->changeMyContactDetails(new ChangeMyContactDetailsRequest($user->getUserId(), $input->toContactDetails()), $this->presenter));
    }
}
