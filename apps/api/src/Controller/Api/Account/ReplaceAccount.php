<?php

namespace App\Controller\Api\Account;

use App\Api\Input\AccountInput;
use App\Api\Resource\AccountResource;
use App\Api\Resource\ProblemResource;
use App\Auth\Passwords;
use App\Entity\User;
use App\Presenter\AccountResourcePresenter;
use App\Presenter\UpdateMyAccountInformationPresenterImpl;
use Nelmio\ApiDocBundle\Attribute\Model;
use OpenApi\Attributes as OA;
use Prospecter\Core\UseCase\GetMyAccountInformation\GetMyAccountInformationRequest;
use Prospecter\Core\UseCase\UpdateMyAccountInformation\UpdateMyAccountInformationRequest;
use Prospecter\Core\UseCase\UseCasesFacade;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;

#[OA\Tag('My account')]
final class ReplaceAccount extends AbstractController
{
    public function __construct(
        private readonly UseCasesFacade $useCases,
        private readonly AccountResourcePresenter $presenter,
        private readonly Passwords $passwords,
    ) {
    }

    #[Route('/api/me', name: 'api_me_replace', methods: ['PUT'])]
    #[OA\Put(summary: 'Replace my account information (a null password keeps the current one; changing the user name ends the session)')]
    #[OA\Response(response: 200, description: 'My updated account', content: new Model(type: AccountResource::class))]
    #[OA\Response(response: 401, description: 'Missing or invalid access token')]
    #[OA\Response(response: 422, description: 'Invalid account', content: new Model(type: ProblemResource::class))]
    public function __invoke(#[MapRequestPayload] AccountInput $input, #[CurrentUser] User $user): JsonResponse
    {
        $this->updateAccountInformation($input, $user);
        $this->changePasswordIfGiven($input, $user);

        return $this->json($this->useCases->getMyAccountInformation(new GetMyAccountInformationRequest(), $this->presenter, $user));
    }

    private function updateAccountInformation(AccountInput $input, User $user): void
    {
        $this->useCases->updateMyAccountInformation(new UpdateMyAccountInformationRequest(
            userName: $input->userName,
            firstName: $input->firstName,
            lastName: $input->lastName,
            picture: null,
            phoneNumber: $input->phoneNumber,
            email: $input->email,
            language: $input->language,
            organizationCorporateName: $input->organization?->corporateName,
            organizationForm: $input->organization?->form,
            organizationLogo: null,
        ), new UpdateMyAccountInformationPresenterImpl(), $user);
    }

    private function changePasswordIfGiven(AccountInput $input, User $user): void
    {
        if (null !== $input->password) {
            $this->passwords->set($user->getUserId(), $input->password);
        }
    }
}
