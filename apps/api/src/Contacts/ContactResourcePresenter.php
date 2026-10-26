<?php

namespace App\Contacts;

use App\Contacts\Resource\ContactLinksResource;
use App\Contacts\Resource\ContactResource;
use App\PersonalData\Resource\PersonNameResource;
use App\Shared\Links;
use App\Shared\Resource\ContactDetailsResource;
use App\Shared\Resource\LinkResource;
use Prospecter\Core\PersonalData\Application\ConsultPerson\ConsultPersonOutput;
use Prospecter\Core\PersonalData\Application\PersonResponse;
use Prospecter\Core\Prospecting\Application\ConsultTimelineIntents\TimelineIntentsResponse;

/**
 * A contact with a link per intention the reader may carry out on it, or on its timeline. Built for one call with
 * what its timeline offers, it presents the person and keeps the resource (ADR 0084).
 */
final class ContactResourcePresenter implements ConsultPersonOutput
{
    private const array ROUTES = [
        'Rename' => ['rename', 'api_contacts_rename'],
        'ChangeJobTitle' => ['changeJobTitle', 'api_contacts_change_job_title'],
        'ChangeContactDetails' => ['changeContactDetails', 'api_contacts_change_contact_details'],
        'Forget' => ['forget', 'api_contacts_forget'],
    ];

    private const array TIMELINE_ROUTES = [
        'ConsultTimeline' => ['exchanges', 'api_contacts_timeline'],
    ];

    public private(set) ContactResource $resource;

    public function __construct(private readonly TimelineIntentsResponse $timeline, private readonly Links $links)
    {
    }

    public function present(PersonResponse $contact): void
    {
        $this->resource = new ContactResource(
            id: $contact->id,
            name: new PersonNameResource($contact->firstName, $contact->lastName),
            jobTitle: $contact->jobTitle,
            contactDetails: new ContactDetailsResource($contact->email, $contact->phoneNumber, $contact->language),
            masked: $contact->masked,
            links: $this->linksOf($contact),
        );
    }

    private function linksOf(PersonResponse $contact): ContactLinksResource
    {
        $id = ['id' => $contact->id];
        $possible = [
            ...$this->possible(self::ROUTES, $contact->possibleIntents, $id),
            ...$this->possible(self::TIMELINE_ROUTES, $this->timeline->possibleIntents, $id),
        ];

        return new ContactLinksResource($this->links->to('api_contacts_show', $id), ...$possible);
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
