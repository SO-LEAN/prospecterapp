<?php

namespace App\Contacts\Controller;

use App\Contacts\ContactReader;
use App\Contacts\Resource\ContactResource;
use App\PersonalData\Input\CivilityInput;
use App\Security\User;
use App\Shared\CommandBus;
use App\Shared\Resource\ProblemResource;
use Nelmio\ApiDocBundle\Attribute\Model;
use OpenApi\Attributes as OA;
use Prospecter\Core\PersonalData\Application\ChangeCivility\ChangeCivility;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Clock\ClockInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Requirement\Requirement;
use Symfony\Component\Security\Http\Attribute\CurrentUser;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[OA\Tag('Contacts')]
#[IsGranted('ROLE_PROSPECTOR')]
final class ChangeCivilityController extends AbstractController
{
    public function __construct(
        private readonly CommandBus $commandBus,
        private readonly ClockInterface $clock,
        private readonly ContactReader $contactReader,
    ) {
    }

    #[Route('/api/contacts/{id}/civility', name: 'api_contacts_change_civility', requirements: ['id' => Requirement::UUID], methods: ['PATCH'])]
    #[OA\Patch(summary: 'Note how one addresses a contact, Madame or Monsieur, or no longer')]
    #[OA\Response(response: 200, description: 'The contact', content: new Model(type: ContactResource::class))]
    #[OA\Response(response: 401, description: 'Missing or invalid access token')]
    #[OA\Response(response: 403, description: 'Not a prospector, or no right to manage personal data', content: new Model(type: ProblemResource::class))]
    #[OA\Response(response: 404, description: 'Unknown contact, or one of another account', content: new Model(type: ProblemResource::class))]
    #[OA\Response(response: 422, description: 'Unknown civility', content: new Model(type: ProblemResource::class))]
    public function __invoke(string $id, #[MapRequestPayload] CivilityInput $input, #[CurrentUser] User $user): JsonResponse
    {
        $this->commandBus->dispatch(new ChangeCivility($user->memberId(), $id, $input->civility, $this->clock->now()));

        return $this->json($this->contactReader->read($user->memberId(), $id));
    }
}
