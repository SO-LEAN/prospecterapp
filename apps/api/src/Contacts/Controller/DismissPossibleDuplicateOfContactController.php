<?php

declare(strict_types=1);

namespace App\Contacts\Controller;

use App\Contacts\ContactReader;
use App\Contacts\Resource\ContactResource;
use App\Security\User;
use App\Shared\CommandBus;
use App\Shared\Resource\ProblemResource;
use Nelmio\ApiDocBundle\Attribute\Model;
use OpenApi\Attributes as OA;
use Prospecter\Core\PersonalData\Application\DismissPossibleDuplicate\DismissPossibleDuplicate;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Clock\ClockInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Requirement\Requirement;
use Symfony\Component\Security\Http\Attribute\CurrentUser;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[OA\Tag('Contacts')]
#[IsGranted('ROLE_PROSPECTOR')]
final class DismissPossibleDuplicateOfContactController extends AbstractController
{
    public function __construct(
        private readonly CommandBus $commandBus,
        private readonly ClockInterface $clock,
        private readonly ContactReader $contactReader,
    ) {
    }

    #[Route('/api/contacts/{id}/possible-duplicates/{duplicateId}', name: 'api_contacts_dismiss_possible_duplicate', requirements: ['id' => Requirement::UUID, 'duplicateId' => Requirement::UUID], methods: ['DELETE'])]
    #[OA\Delete(summary: 'Say, once checked, that the contact is not a duplicate of the one they resemble. Neither of them is flagged for the other any more')]
    #[OA\Response(response: 200, description: 'The contact', content: new Model(type: ContactResource::class))]
    #[OA\Response(response: 401, description: 'Missing or invalid access token')]
    #[OA\Response(response: 403, description: 'Not a prospector, or no right to manage personal data', content: new Model(type: ProblemResource::class))]
    #[OA\Response(response: 404, description: 'The contact is unknown or belongs to another account', content: new Model(type: ProblemResource::class))]
    #[OA\Response(response: 409, description: 'The contact is not suspected of being a duplicate of this one (notASuspectedDuplicateContact)', content: new Model(type: ProblemResource::class))]
    public function __invoke(string $id, string $duplicateId, #[CurrentUser] User $user): JsonResponse
    {
        $this->commandBus->dispatch(new DismissPossibleDuplicate(readerId: $user->memberId(), id: $id, duplicateId: $duplicateId, executedAt: $this->clock->now()));

        return $this->json($this->contactReader->read($user->memberId(), $id));
    }
}
