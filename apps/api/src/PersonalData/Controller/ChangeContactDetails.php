<?php

namespace App\PersonalData\Controller;

use App\PersonalData\ContactReader;
use App\PersonalData\Resource\ContactResource;
use App\Security\User;
use App\Shared\CommandBus;
use App\Shared\Input\ContactDetailsInput;
use App\Shared\Resource\ProblemResource;
use Nelmio\ApiDocBundle\Attribute\Model;
use OpenApi\Attributes as OA;
use Prospecter\Core\PersonalData\Application\ChangePersonContactDetails\ChangePersonContactDetails;
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
final class ChangeContactDetails extends AbstractController
{
    public function __construct(
        private readonly CommandBus $commands,
        private readonly ClockInterface $clock,
        private readonly ContactReader $contacts,
    ) {
    }

    #[Route('/api/contacts/{id}/contact-details', name: 'api_contacts_change_contact_details', requirements: ['id' => Requirement::UUID], methods: ['PATCH'])]
    #[OA\Patch(summary: 'Change the email, phone number and language of a contact')]
    #[OA\Response(response: 200, description: 'The contact', content: new Model(type: ContactResource::class))]
    #[OA\Response(response: 401, description: 'Missing or invalid access token')]
    #[OA\Response(response: 403, description: 'Not a prospector, or no right to manage personal data', content: new Model(type: ProblemResource::class))]
    #[OA\Response(response: 404, description: 'Unknown contact, or one of another account', content: new Model(type: ProblemResource::class))]
    #[OA\Response(response: 422, description: 'Invalid contact details, or email already used by another contact', content: new Model(type: ProblemResource::class))]
    public function __invoke(string $id, #[MapRequestPayload] ContactDetailsInput $input, #[CurrentUser] User $user): JsonResponse
    {
        $this->commands->dispatch(new ChangePersonContactDetails($user->memberId(), $id, $input->email, $input->phoneNumber, $input->language, $this->clock->now()));

        return $this->json($this->contacts->read($user->memberId(), $id));
    }
}
