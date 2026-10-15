<?php

namespace App\OrganizationContacts;

use App\OrganizationContacts\Resource\OrganizationContactLinksResource;
use App\OrganizationContacts\Resource\OrganizationContactResource;
use App\OrganizationContacts\Resource\OrganizationContactsResource;
use App\PersonalData\Resource\PersonNameResource;
use App\Security\User;
use App\Shared\Links;
use App\Shared\Resource\ContactDetailsResource;
use App\Shared\Resource\ProblemResource;
use Nelmio\ApiDocBundle\Attribute\Model;
use OpenApi\Attributes as OA;
use Prospecter\Core\PersonalData\Application\ConsultPersons\ConsultPersons;
use Prospecter\Core\PersonalData\Application\ConsultPersons\ConsultPersonsHandler;
use Prospecter\Core\PersonalData\Application\PersonResponse;
use Prospecter\Core\Prospecting\Application\ConsultOrganization\ConsultOrganization;
use Prospecter\Core\Prospecting\Application\ConsultOrganization\ConsultOrganizationHandler;
use Prospecter\Core\Prospecting\Application\OrganizationResponse;
use Prospecter\Core\SharedKernel\Application\RefusalTranslatingHandler;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Requirement\Requirement;
use Symfony\Component\Security\Http\Attribute\CurrentUser;
use Symfony\Component\Security\Http\Attribute\IsGranted;

/**
 * The contacts of an organization, composed of two contexts (ADR 0041): Prospecting knows which persons the
 * organization has, PersonalData reads them, in clear or masked, in one batch. An erased person is left out.
 */
#[OA\Tag('Organizations')]
#[IsGranted('ROLE_PROSPECTOR')]
final class ShowOrganizationContacts extends AbstractController
{
    private readonly RefusalTranslatingHandler $consultOrganization;
    private readonly RefusalTranslatingHandler $consultPersons;

    public function __construct(ConsultOrganizationHandler $consultOrganization, ConsultPersonsHandler $consultPersons, private readonly Links $links)
    {
        $this->consultOrganization = new RefusalTranslatingHandler($consultOrganization);
        $this->consultPersons = new RefusalTranslatingHandler($consultPersons);
    }

    #[Route('/api/organizations/{id}/contacts', name: 'api_organizations_contacts', requirements: ['id' => Requirement::UUID], methods: ['GET'])]
    #[OA\Get(summary: 'The contacts of the organization, in clear or masked')]
    #[OA\Response(response: 200, description: 'The contacts, in the order they were attached', content: new Model(type: OrganizationContactsResource::class))]
    #[OA\Response(response: 401, description: 'Missing or invalid access token')]
    #[OA\Response(response: 403, description: 'The signed-in user is not a prospector', content: new Model(type: ProblemResource::class))]
    #[OA\Response(response: 404, description: 'Unknown organization, or one of another account', content: new Model(type: ProblemResource::class))]
    public function __invoke(string $id, #[CurrentUser] User $user): JsonResponse
    {
        $organization = ($this->consultOrganization)(new ConsultOrganization($user->memberId(), $id));
        $persons = ($this->consultPersons)(new ConsultPersons($user->memberId(), $organization->contactIds));
        $contacts = array_values(array_filter(array_map(static fn (string $contactId): ?PersonResponse => $persons[$contactId] ?? null, $organization->contactIds)));

        return $this->json(new OrganizationContactsResource(array_map(fn (PersonResponse $contact): OrganizationContactResource => $this->present($contact, $organization), $contacts)));
    }

    private function present(PersonResponse $contact, OrganizationResponse $organization): OrganizationContactResource
    {
        return new OrganizationContactResource(
            id: $contact->id,
            name: new PersonNameResource($contact->firstName, $contact->lastName),
            jobTitle: $contact->jobTitle,
            contactDetails: new ContactDetailsResource($contact->email, $contact->phoneNumber, $contact->language),
            masked: $contact->masked,
            links: new OrganizationContactLinksResource(
                $this->links->to('api_contacts_show', ['id' => $contact->id]),
                in_array('RemoveContact', $organization->possibleIntents, true) ? $this->links->to('api_organizations_remove_contact', ['id' => $organization->id, 'personId' => $contact->id]) : null,
            ),
        );
    }
}
