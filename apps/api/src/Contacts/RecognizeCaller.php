<?php

namespace App\Contacts;

use App\Contacts\Resource\CallerResource;
use App\Contacts\Resource\ContactInListResource;
use App\Prospecting\Presenter\OrganizationResourcePresenter;
use App\Security\User;
use App\Shared\Resource\ProblemResource;
use Nelmio\ApiDocBundle\Attribute\Model;
use OpenApi\Attributes as OA;
use Prospecter\Core\PersonalData\Application\PersonResponse;
use Prospecter\Core\PersonalData\Application\RecognizeCallingContact\RecognizeCallingContact;
use Prospecter\Core\Prospecting\Application\OrganizationResponse;
use Prospecter\Core\Prospecting\Application\RecognizeCallingOrganization\RecognizeCallingOrganization;
use Prospecter\Core\SharedKernel\Application\RefusalTranslatingHandler;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;
use Symfony\Component\Security\Http\Attribute\IsGranted;

/**
 * Who calls (ADR 0072), composed of two contexts: Prospecting tells the organizations having the number,
 * PersonalData the contacts, read in clear; Prospecting then the organizations of each contact.
 */
#[OA\Tag('Contacts')]
#[IsGranted('ROLE_PROSPECTOR')]
final class RecognizeCaller extends AbstractController
{
    public function __construct(
        private readonly RefusalTranslatingHandler $recognizeCallingOrganization,
        private readonly RefusalTranslatingHandler $recognizeCallingContact,
        private readonly OrganizationsOfContacts $organizationsOfContacts,
        private readonly OrganizationResourcePresenter $organizations,
        private readonly ContactInListPresenter $contacts,
    ) {
    }

    #[Route('/api/phone-numbers/{phoneNumber}', name: 'api_phone_numbers_recognize', methods: ['GET'])]
    #[OA\Get(summary: 'Recognize who calls: the organizations my organization prospects and its contacts having the phone number, given in E.164 or in the national format of France; needs the right to manage personal data')]
    #[OA\Response(response: 200, description: 'Who has the number, nobody when both lists are empty', content: new Model(type: CallerResource::class))]
    #[OA\Response(response: 401, description: 'Missing or invalid access token')]
    #[OA\Response(response: 403, description: 'Not a prospector, or without the right to manage personal data', content: new Model(type: ProblemResource::class))]
    #[OA\Response(response: 422, description: 'No valid phone number', content: new Model(type: ProblemResource::class))]
    public function __invoke(string $phoneNumber, #[CurrentUser] User $user): JsonResponse
    {
        /** @var list<OrganizationResponse> $organizations */
        $organizations = ($this->recognizeCallingOrganization)(new RecognizeCallingOrganization($user->memberId(), $phoneNumber));
        /** @var list<PersonResponse> $contacts */
        $contacts = ($this->recognizeCallingContact)(new RecognizeCallingContact($user->memberId(), $phoneNumber));
        $organizationsOfContacts = $this->organizationsOfContacts->read($user->memberId(), array_map(static fn (PersonResponse $contact): string => $contact->id, $contacts));

        return $this->json(new CallerResource(
            array_map($this->organizations->present(...), $organizations),
            array_map(fn (PersonResponse $contact): ContactInListResource => $this->contacts->present($contact, $organizationsOfContacts[$contact->id] ?? []), $contacts),
        ));
    }
}
