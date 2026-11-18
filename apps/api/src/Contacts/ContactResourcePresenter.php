<?php

namespace App\Contacts;

use App\Contacts\Resource\ContactLinksResource;
use App\Contacts\Resource\ContactResource;
use App\Contacts\Resource\ToAttachResource;
use App\PersonalData\Resource\PersonContactDetailsResource;
use App\PersonalData\Resource\PersonNameResource;
use App\Security\IntentLinks;
use App\Security\User;
use App\Shared\Links;
use App\Shared\Resource\LinkResource;
use Prospecter\Core\PersonalData\Application\ConsultPerson\ConsultPersonOutput;
use Prospecter\Core\PersonalData\Application\PersonCard;
use Prospecter\Core\Prospecting\Application\ConsultTimelineIntents\TimelineIntentsResponse;
use Prospecter\Core\Prospecting\Application\ContactToAttachInList;

/**
 * A contact with a link per intention the reader may carry out on it, or on its timeline, and until when they wait
 * for the reader to attach them (ADR 0146). Built for one call with what its timeline offers, it presents the person
 * and keeps the resource (ADR 0085).
 */
final class ContactResourcePresenter implements ConsultPersonOutput
{
    private const array ROUTES = [
        'Rename' => ['rename', 'api_contacts_rename'],
        'ChangeJobTitle' => ['changeJobTitle', 'api_contacts_change_job_title'],
        'ChangeCivility' => ['changeCivility', 'api_contacts_change_civility'],
        'ChangeContactDetails' => ['changeContactDetails', 'api_contacts_change_contact_details'],
        'Forget' => ['forget', 'api_contacts_forget'],
    ];

    private const array TIMELINE_ROUTES = [
        'ConsultTimeline' => ['exchanges', 'api_contacts_timeline'],
    ];

    public private(set) ContactResource $resource;

    public function __construct(private readonly TimelineIntentsResponse $timeline, private readonly ?ContactToAttachInList $toAttach, private readonly Links $links, private readonly IntentLinks $intentLinks)
    {
    }

    public function present(PersonCard $contact): void
    {
        $this->resource = new ContactResource(
            id: $contact->id,
            name: new PersonNameResource($contact->firstName, $contact->lastName),
            civility: $contact->civility,
            jobTitle: $contact->jobTitle,
            contactDetails: PersonContactDetailsResource::of($contact),
            masked: $contact->masked,
            toAttach: null === $this->toAttach ? null : new ToAttachResource($this->toAttach->lastMeetingAt, $this->toAttach->erasableFrom),
            links: $this->linksOf($contact),
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
            'tags' => $this->links->to('api_contacts_tags', $id),
        ];

        return new ContactLinksResource($this->links->to('api_contacts_show', $id), ...$possible);
    }

    /**
     * The to-dos of a contact are read with the right to manage personal data, their text possibly holding some.
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
     * The organization to attach a contact waiting for the reader is proposed with the right to manage personal data,
     * which attaching needs (ADR 0146).
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
     * @param array<string, array{string, string}> $routes  by intent, the name of its link and its route
     * @param list<string>                         $intents
     * @param array<string, string>                $id
     *
     * @return array<string, LinkResource> by name of link
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
