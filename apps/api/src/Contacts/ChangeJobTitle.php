<?php

namespace App\Contacts;

use App\Contacts\Resource\ContactResource;
use App\PersonalData\Input\JobTitleInput;
use App\Security\User;
use App\Shared\CommandBus;
use App\Shared\Resource\ProblemResource;
use Nelmio\ApiDocBundle\Attribute\Model;
use OpenApi\Attributes as OA;
use Prospecter\Core\PersonalData\Application\ChangeJobTitle\ChangeJobTitle as ChangeJobTitleCommand;
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
final class ChangeJobTitle extends AbstractController
{
    public function __construct(
        private readonly CommandBus $commands,
        private readonly ClockInterface $clock,
        private readonly ContactReader $contacts,
    ) {
    }

    #[Route('/api/contacts/{id}/job-title', name: 'api_contacts_change_job_title', requirements: ['id' => Requirement::UUID], methods: ['PATCH'])]
    #[OA\Patch(summary: 'Change what a contact does in their organization')]
    #[OA\Response(response: 200, description: 'The contact', content: new Model(type: ContactResource::class))]
    #[OA\Response(response: 401, description: 'Missing or invalid access token')]
    #[OA\Response(response: 403, description: 'Not a prospector, or no right to manage personal data', content: new Model(type: ProblemResource::class))]
    #[OA\Response(response: 404, description: 'Unknown contact, or one of another account', content: new Model(type: ProblemResource::class))]
    #[OA\Response(response: 422, description: 'Invalid job title', content: new Model(type: ProblemResource::class))]
    public function __invoke(string $id, #[MapRequestPayload] JobTitleInput $input, #[CurrentUser] User $user): JsonResponse
    {
        $this->commands->dispatch(new ChangeJobTitleCommand($user->memberId(), $id, $input->jobTitle, $this->clock->now()));

        return $this->json($this->contacts->read($user->memberId(), $id));
    }
}
