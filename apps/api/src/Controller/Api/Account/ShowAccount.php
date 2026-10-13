<?php

namespace App\Controller\Api\Account;

use App\Api\Resource\AccountResource;
use App\Entity\User;
use App\Presenter\AccountResourcePresenter;
use Nelmio\ApiDocBundle\Attribute\Model;
use OpenApi\Attributes as OA;
use Prospecter\Core\UseCase\GetMyAccountInformation\GetMyAccountInformationRequest;
use Prospecter\Core\UseCase\UseCasesFacade;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;

#[OA\Tag('My account')]
final class ShowAccount extends AbstractController
{
    public function __construct(
        private readonly UseCasesFacade $useCases,
        private readonly AccountResourcePresenter $presenter,
    ) {
    }

    #[Route('/api/me', name: 'api_me_show', methods: ['GET'])]
    #[OA\Get(summary: 'Get my account and my organization')]
    #[OA\Response(response: 200, description: 'My account', content: new Model(type: AccountResource::class))]
    #[OA\Response(response: 401, description: 'Missing or invalid access token')]
    public function __invoke(#[CurrentUser] User $user): JsonResponse
    {
        return $this->json($this->useCases->getMyAccountInformation(new GetMyAccountInformationRequest(), $this->presenter, $user));
    }
}
