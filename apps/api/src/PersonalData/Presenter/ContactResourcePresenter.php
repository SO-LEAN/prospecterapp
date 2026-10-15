<?php

namespace App\PersonalData\Presenter;

use App\PersonalData\Resource\ContactLinksResource;
use App\PersonalData\Resource\ContactResource;
use App\PersonalData\Resource\PersonNameResource;
use App\Shared\Links;
use App\Shared\Resource\ContactDetailsResource;
use Prospecter\Core\PersonalData\Application\PersonResponse;

/**
 * A contact with a link per intention the reader may carry out on it.
 */
final readonly class ContactResourcePresenter
{
    private const array ROUTES = [
        'Rename' => ['rename', 'api_contacts_rename'],
        'ChangeJobTitle' => ['changeJobTitle', 'api_contacts_change_job_title'],
        'ChangeContactDetails' => ['changeContactDetails', 'api_contacts_change_contact_details'],
        'Forget' => ['forget', 'api_contacts_forget'],
    ];

    public function __construct(private Links $links)
    {
    }

    public function present(PersonResponse $contact): ContactResource
    {
        return new ContactResource(
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
        $possible = [];

        foreach ($contact->possibleIntents as $intent) {
            if (isset(self::ROUTES[$intent])) {
                [$name, $route] = self::ROUTES[$intent];
                $possible[$name] = $this->links->to($route, $id);
            }
        }

        return new ContactLinksResource($this->links->to('api_contacts_show', $id), ...$possible);
    }
}
