<?php

declare(strict_types=1);

namespace App\Me\Controller;

use App\Me\MyAccountReader;
use App\Me\Resource\AccountResource;
use App\PersonalData\Input\PersonContactDetailsInput;
use App\Security\User;
use App\Shared\CommandBus;
use App\Shared\JsonResponses;
use App\Shared\Resource\ProblemResource;
use Nelmio\ApiDocBundle\Attribute\Model;
use OpenApi\Attributes as OA;
use Prospecter\Core\PersonalData\Application\ChangePersonContactDetails\ChangePersonContactDetails;
use Symfony\Component\Clock\ClockInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[OA\Tag('My account')]
#[IsGranted('ROLE_USER')]
final readonly class ChangeMyContactDetailsController
{
    public function __construct(
        private CommandBus $commandBus,
        private ClockInterface $clock,
        private MyAccountReader $myAccountReader,
        private JsonResponses $jsonResponses,
    ) {
    }

    #[Route('/api/me/contact-details', name: 'api_me_change_contact_details', methods: ['PATCH'])]
    #[OA\Patch(summary: 'Change my email, phone number, language and links. Nothing else changes.')]
    #[OA\Response(response: 200, description: 'My account', content: new Model(type: AccountResource::class))]
    #[OA\Response(response: 401, description: 'Missing or invalid access token')]
    #[OA\Response(response: 403, description: 'The signed-in user is not a user', content: new Model(type: ProblemResource::class))]
    #[OA\Response(response: 422, description: 'Invalid contact details or email already used', content: new Model(type: ProblemResource::class))]
    public function __invoke(#[MapRequestPayload] PersonContactDetailsInput $input, #[CurrentUser] User $user): JsonResponse
    {
        $this->commandBus->dispatch(new ChangePersonContactDetails(readerId: $user->memberId(), id: $user->personId(), email: $input->email, phoneNumber: $input->phoneNumber, language: $input->language, links: $input->links, executedAt: $this->clock->now()));

        return $this->jsonResponses->of($this->myAccountReader->read($user->memberId()));
    }
}
