<?php

declare(strict_types=1);

namespace App\Contacts\Controller;

use App\Contacts\ContactComposition;
use App\Contacts\Resource\ContactResource;
use App\PersonalData\Input\PersonContactDetailsInput;
use App\PersonalData\Persistence\PersonRevisions;
use App\Security\User;
use App\Shared\CommandBus;
use App\Shared\JsonResponses;
use App\Shared\Resource\ProblemResource;
use App\Shared\Resource\RevisionDescription;
use Nelmio\ApiDocBundle\Attribute\Model;
use OpenApi\Attributes as OA;
use Prospecter\Core\PersonalData\Application\Person\ChangePersonContactDetails\ChangePersonContactDetails;
use Symfony\Component\Clock\ClockInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Attribute\MapQueryParameter;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Requirement\Requirement;
use Symfony\Component\Security\Http\Attribute\CurrentUser;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[OA\Tag('Contacts')]
#[IsGranted('ROLE_PROSPECTOR')]
final readonly class ChangeContactDetailsController
{
    public function __construct(
        private CommandBus $commandBus,
        private ClockInterface $clock,
        private ContactComposition $contactComposition,
        private PersonRevisions $revisions,
        private JsonResponses $jsonResponses,
    ) {
    }

    #[Route('/api/contacts/{id}/contact-details', name: 'api_contacts_change_contact_details', requirements: ['id' => Requirement::UUID], methods: ['PATCH'])]
    #[OA\Patch(summary: 'Change a contact\'s email, phone number, language and links')]
    #[OA\Parameter(name: 'revision', in: 'query', description: RevisionDescription::OF_A_CHANGE)]
    #[OA\Response(response: 200, description: 'The contact', content: new Model(type: ContactResource::class))]
    #[OA\Response(response: 401, description: 'Missing or invalid access token')]
    #[OA\Response(response: 403, description: 'Not a prospector, or no right to manage personal data', content: new Model(type: ProblemResource::class))]
    #[OA\Response(response: 404, description: 'The contact is unknown or belongs to another account', content: new Model(type: ProblemResource::class))]
    #[OA\Response(response: 409, description: 'Changed since the revision sent was read', content: new Model(type: ProblemResource::class))]
    #[OA\Response(response: 422, description: 'Invalid contact details, or email or link already used by another contact', content: new Model(type: ProblemResource::class))]
    public function __invoke(string $id, #[MapRequestPayload] PersonContactDetailsInput $input, #[CurrentUser] User $user, #[MapQueryParameter(options: ['min_range' => 0], validationFailedStatusCode: 422)] ?int $revision = null): JsonResponse
    {
        $this->commandBus->dispatch(new ChangePersonContactDetails(readerId: $user->memberId(), id: $id, email: $input->email, phoneNumber: $input->phoneNumber, language: $input->language, links: $input->links, executedAt: $this->clock->now()), $this->revisions->readAt($id, $revision));

        return $this->jsonResponses->of($this->contactComposition->read($user->memberId(), $id));
    }
}
