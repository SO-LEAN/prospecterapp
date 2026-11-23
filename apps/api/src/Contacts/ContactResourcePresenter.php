<?php

declare(strict_types=1);

namespace App\Contacts;

use App\Contacts\Resource\ContactLinksResource;
use App\Contacts\Resource\ContactResource;
use App\Contacts\Resource\PossibleDuplicateOfContactLinksResource;
use App\Contacts\Resource\PossibleDuplicateOfContactResource;
use App\Contacts\Resource\ToAttachResource;
use App\PersonalData\Persistence\PersonRevisions;
use App\PersonalData\Resource\PersonContactDetailsResource;
use App\PersonalData\Resource\PersonNameResource;
use App\Security\IntentLinks;
use App\Security\User;
use App\Shared\Links;
use App\Shared\Resource\LinkResource;
use Prospecter\Core\PersonalData\Application\ConsultPerson\ConsultPersonOutput;
use Prospecter\Core\PersonalData\Application\PersonCard;
use Prospecter\Core\PersonalData\Application\PossibleDuplicate;
use Prospecter\Core\Prospecting\Application\ConsultTimelineIntents\TimelineIntentsResponse;
use Prospecter\Core\Prospecting\Application\ContactToAttachInList;

/**
 * Presents a contact with a link per intent the reader may carry out on the contact or on their timeline. It also
 * says until when the contact waits for the reader to attach them (ADR 0146). One presenter serves one call. It gets
 * what the timeline offers, presents the person and keeps the resource (ADR 0085).
 */
final class ContactResourcePresenter implements ConsultPersonOutput
{
    private const array ROUTES = [
        'Rename' => ['rename', 'api_contacts_rename'],
        'ChangeJobTitle' => ['changeJobTitle', 'api_contacts_change_job_title'],
        'ChangeCivility' => ['changeTitle', 'api_contacts_change_title'],
        'ChangeContactDetails' => ['changeContactDetails', 'api_contacts_change_contact_details'],
        'Forget' => ['forget', 'api_contacts_forget'],
    ];

    private const array TIMELINE_ROUTES = [
        'ConsultTimeline' => ['exchanges', 'api_contacts_timeline'],
    ];

    public private(set) ContactResource $resource;

    private const string CATCH_UP = 'CatchUpGmail';
    private const string CATCH_UP_ROUTE = 'api_contacts_reimport_gmail';

    /**
     * @param list<string> $catchUpIntents what Connectors offers to do with the e-mails exchanged with the contact
     */
    public function __construct(private readonly TimelineIntentsResponse $timeline, private readonly ?ContactToAttachInList $toAttach, private readonly array $catchUpIntents, private readonly Links $links, private readonly IntentLinks $intentLinks, private readonly PersonRevisions $revisions)
    {
    }

    public function present(PersonCard $contact): void
    {
        $this->resource = new ContactResource(
            id: $contact->id,
            name: new PersonNameResource($contact->firstName, $contact->lastName),
            title: $contact->civility,
            jobTitle: $contact->jobTitle,
            contactDetails: PersonContactDetailsResource::of($contact),
            masked: $contact->masked,
            toAttach: null === $this->toAttach ? null : new ToAttachResource($this->toAttach->lastMeetingAt, $this->toAttach->erasableFrom),
            links: $this->linksOf($contact),
            possibleDuplicates: array_map(fn (PossibleDuplicate $duplicate): PossibleDuplicateOfContactResource => $this->possibleDuplicateOf($contact, $duplicate), $contact->possibleDuplicates),
            createdByAnAI: $contact->createdByAnAI,
            revision: $this->revisions->lastRead($contact->id),
        );
    }

    /**
     * Names the possible duplicate with a link to them. When the reader may, it adds a link to say, once checked, that
     * the contact is not their duplicate.
     */
    private function possibleDuplicateOf(PersonCard $contact, PossibleDuplicate $duplicate): PossibleDuplicateOfContactResource
    {
        $dismiss = in_array('DismissPossibleDuplicate', $contact->possibleIntents, true) ? $this->links->to('api_contacts_dismiss_possible_duplicate', ['id' => $contact->id, 'duplicateId' => $duplicate->id]) : null;

        return new PossibleDuplicateOfContactResource(
            $duplicate->id,
            new PersonNameResource($duplicate->firstName, $duplicate->lastName),
            new PossibleDuplicateOfContactLinksResource($this->links->to('api_contacts_show', ['id' => $duplicate->id]), $dismiss),
        );
    }

    private function linksOf(PersonCard $contact): ContactLinksResource
    {
        $id = ['id' => $contact->id];
        $possible = [
            ...$this->possible(self::ROUTES, $contact->possibleIntents, $id),
            ...$this->possible(self::TIMELINE_ROUTES, $this->timeline->possibleIntents, $id),
            ...$this->toDosOf($id),
            ...$this->proposalOf($id),
            ...$this->catchUpOf($id),
            'tags' => $this->links->to('api_contacts_tags', $id),
        ];

        return new ContactLinksResource($this->links->to('api_contacts_show', $id), ...$possible);
    }

    /**
     * Reading a contact's to-dos takes the right to manage personal data, because their text may hold some.
     *
     * @param array<string, string> $id
     *
     * @return array<string, LinkResource>
     */
    private function toDosOf(array $id): array
    {
        return $this->intentLinks->shows('ConsultTimeline', $this->timeline->possibleIntents, User::PERSONAL_DATA) ? ['toDos' => $this->links->to('api_contacts_to_dos', $id)] : [];
    }

    /**
     * The organization to attach a waiting contact to is proposed only with the right to manage personal data.
     * Attaching the contact needs that right (ADR 0146).
     *
     * @param array<string, string> $id
     *
     * @return array<string, LinkResource>
     */
    private function proposalOf(array $id): array
    {
        return null !== $this->toAttach && $this->intentLinks->holds(User::PERSONAL_DATA) ? ['proposedOrganization' => $this->links->to('api_contacts_proposed_organization', $id)] : [];
    }

    /**
     * Catching up the e-mails exchanged with a contact takes the right to manage personal data, because it imports
     * them (ADR 0081, 0176).
     *
     * @param array<string, string> $id
     *
     * @return array<string, LinkResource>
     */
    private function catchUpOf(array $id): array
    {
        return $this->intentLinks->shows(self::CATCH_UP, $this->catchUpIntents, User::PERSONAL_DATA) ? ['reimportGmail' => $this->links->to(self::CATCH_UP_ROUTE, $id)] : [];
    }

    /**
     * @param array<string, array{string, string}> $routes  by intent, its link's name and its route
     * @param list<string>                         $intents
     * @param array<string, string>                $id
     *
     * @return array<string, LinkResource> by link name
     */
    private function possible(array $routes, array $intents, array $id): array
    {
        $possible = [];

        foreach ($intents as $intent) {
            if (isset($routes[$intent])) {
                [$name, $route] = $routes[$intent];
                $possible[$name] = $this->links->to($route, $id);
            }
        }

        return $possible;
    }
}
