<?php

declare(strict_types=1);

namespace App\Contacts\Controller;

use App\Contacts\ContactReader;
use App\Contacts\Resource\ContactResource;
use App\PersonalData\Input\TitleInput;
use App\PersonalData\Persistence\PersonRevisions;
use App\Security\User;
use App\Shared\CommandBus;
use App\Shared\JsonResponses;
use App\Shared\Resource\ProblemResource;
use App\Shared\Resource\RevisionDescription;
use Nelmio\ApiDocBundle\Attribute\Model;
use OpenApi\Attributes as OA;
use Prospecter\Core\PersonalData\Application\Person\ChangeCivility\ChangeCivility;
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
final readonly class ChangeTitleController
{
    public function __construct(
        private CommandBus $commandBus,
        private ClockInterface $clock,
        private ContactReader $contactReader,
        private PersonRevisions $revisions,
        private JsonResponses $jsonResponses,
    ) {
    }

    #[Route('/api/contacts/{id}/title', name: 'api_contacts_change_title', requirements: ['id' => Requirement::UUID], methods: ['PATCH'])]
    #[OA\Patch(summary: 'Set how to address a contact: Ms, Mr, or unknown (x)')]
    #[OA\Parameter(name: 'revision', in: 'query', description: RevisionDescription::OF_A_CHANGE)]
    #[OA\Response(response: 200, description: 'The contact', content: new Model(type: ContactResource::class))]
    #[OA\Response(response: 401, description: 'Missing or invalid access token')]
    #[OA\Response(response: 403, description: 'Not a prospector, or no right to manage personal data', content: new Model(type: ProblemResource::class))]
    #[OA\Response(response: 404, description: 'The contact is unknown or belongs to another account', content: new Model(type: ProblemResource::class))]
    #[OA\Response(response: 409, description: 'Changed since the revision sent was read', content: new Model(type: ProblemResource::class))]
    #[OA\Response(response: 422, description: 'Unknown title', content: new Model(type: ProblemResource::class))]
    public function __invoke(string $id, #[MapRequestPayload] TitleInput $input, #[CurrentUser] User $user, #[MapQueryParameter(options: ['min_range' => 0], validationFailedStatusCode: 422)] ?int $revision = null): JsonResponse
    {
        $this->commandBus->dispatch(new ChangeCivility(readerId: $user->memberId(), id: $id, civility: $input->title, executedAt: $this->clock->now()), $this->revisions->readAt($id, $revision));

        return $this->jsonResponses->of($this->contactReader->read($user->memberId(), $id));
    }
}
