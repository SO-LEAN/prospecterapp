<?php

namespace App\Accounts\Controller;

use App\Accounts\AccountReader;
use App\Accounts\Resource\AccountResource;
use App\Entity\User;
use App\Shared\CommandBus;
use App\Shared\Input\ContactDetailsInput;
use App\Shared\Resource\ProblemResource;
use Nelmio\ApiDocBundle\Attribute\Model;
use OpenApi\Attributes as OA;
use Prospecter\Core\Accounts\Application\ChangeMyContactDetails\ChangeMyContactDetails as ChangeMyContactDetailsCommand;
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
        private readonly CommandBus $commands,
        private readonly AccountReader $account,
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
        $this->commands->dispatch(new ChangeMyContactDetailsCommand($user->memberId(), $input->toContactDetails()));

        return $this->json($this->account->read($user->memberId()));
    }
}
