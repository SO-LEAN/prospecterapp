<?php

declare(strict_types=1);

namespace App\Contacts\Controller;

use App\Contacts\ContactReader;
use App\Contacts\Resource\ContactResource;
use App\PersonalData\Input\CollectContactInput;
use App\Security\User;
use App\Shared\CommandBus;
use App\Shared\Identities;
use App\Shared\JsonResponses;
use App\Shared\Resource\ProblemResource;
use Nelmio\ApiDocBundle\Attribute\Model;
use OpenApi\Attributes as OA;
use Symfony\Component\Clock\ClockInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Http\Attribute\CurrentUser;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[OA\Tag('Contacts')]
#[IsGranted('ROLE_PROSPECTOR')]
final readonly class CollectContactController
{
    public function __construct(
        private CommandBus $commandBus,
        private ClockInterface $clock,
        private Identities $identities,
        private ContactReader $contactReader,
        private JsonResponses $jsonResponses,
        private UrlGeneratorInterface $urlGenerator,
    ) {
    }

    #[Route('/api/contacts', name: 'api_contacts_collect', methods: ['POST'])]
    #[OA\Post(summary: 'Collect a person my organization prospects, as a contact of the organizations they work for. At least one organization is required')]
    #[OA\Response(response: 201, description: 'The contact', content: new Model(type: ContactResource::class))]
    #[OA\Response(response: 401, description: 'Missing or invalid access token')]
    #[OA\Response(response: 403, description: 'Not a prospector, or no right to manage personal data', content: new Model(type: ProblemResource::class))]
    #[OA\Response(response: 404, description: 'The organization is unknown or belongs to another account', content: new Model(type: ProblemResource::class))]
    #[OA\Response(response: 422, description: 'Invalid contact, no organization, or email already used by another contact', content: new Model(type: ProblemResource::class))]
    public function __invoke(#[MapRequestPayload] CollectContactInput $input, #[CurrentUser] User $user): JsonResponse
    {
        $id = $this->identities->next();
        $this->commandBus->dispatch($input->commandOf($user->memberId(), $id, $this->clock->now()));

        return $this->jsonResponses->of($this->contactReader->read($user->memberId(), $id), Response::HTTP_CREATED, ['Location' => $this->urlGenerator->generate('api_contacts_show', ['id' => $id])]);
    }
}
