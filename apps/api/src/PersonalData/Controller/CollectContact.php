<?php

namespace App\PersonalData\Controller;

use App\PersonalData\ContactReader;
use App\PersonalData\Input\CollectContactInput;
use App\PersonalData\Resource\ContactResource;
use App\Security\User;
use App\Shared\CommandBus;
use App\Shared\Identities;
use App\Shared\Resource\ProblemResource;
use Nelmio\ApiDocBundle\Attribute\Model;
use OpenApi\Attributes as OA;
use Prospecter\Core\PersonalData\Application\CollectContact\CollectContact as CollectContactCommand;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Clock\ClockInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[OA\Tag('Contacts')]
#[IsGranted('ROLE_PROSPECTOR')]
final class CollectContact extends AbstractController
{
    public function __construct(
        private readonly CommandBus $commands,
        private readonly ClockInterface $clock,
        private readonly Identities $identities,
        private readonly ContactReader $contacts,
    ) {
    }

    #[Route('/api/contacts', name: 'api_contacts_collect', methods: ['POST'])]
    #[OA\Post(summary: 'Collect a person my organization prospects, as a contact of the organizations they work for (at least one)')]
    #[OA\Response(response: 201, description: 'The contact', content: new Model(type: ContactResource::class))]
    #[OA\Response(response: 401, description: 'Missing or invalid access token')]
    #[OA\Response(response: 403, description: 'Not a prospector, or no right to manage personal data', content: new Model(type: ProblemResource::class))]
    #[OA\Response(response: 404, description: 'Unknown organization, or one of another account', content: new Model(type: ProblemResource::class))]
    #[OA\Response(response: 422, description: 'Invalid contact, no organization, or email already used by another contact', content: new Model(type: ProblemResource::class))]
    public function __invoke(#[MapRequestPayload] CollectContactInput $input, #[CurrentUser] User $user): JsonResponse
    {
        $id = $this->identities->next();
        $this->commands->dispatch(new CollectContactCommand(
            readerId: $user->memberId(),
            id: $id,
            firstName: $input->name->firstName,
            lastName: $input->name->lastName,
            jobTitle: $input->jobTitle,
            email: $input->contactDetails->email,
            phoneNumber: $input->contactDetails->phoneNumber,
            language: $input->contactDetails->language,
            organizationIds: $input->organizationIds,
            executedAt: $this->clock->now(),
        ));

        return $this->json($this->contacts->read($user->memberId(), $id), Response::HTTP_CREATED, ['Location' => $this->generateUrl('api_contacts_show', ['id' => $id])]);
    }
}
