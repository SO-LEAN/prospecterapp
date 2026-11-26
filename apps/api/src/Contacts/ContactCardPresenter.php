<?php

declare(strict_types=1);

namespace App\Contacts;

use App\Contacts\Resource\ContactProfileLinksResource;
use App\Contacts\Resource\PossibleDuplicateOfContactLinksResource;
use App\Contacts\Resource\PossibleDuplicateOfContactResource;
use App\PersonalData\Resource\PersonNameResource;
use App\Shared\Links;
use App\Shared\Resource\LinkResource;
use Prospecter\Core\PersonalData\Application\Person\ConsultPerson\ConsultPersonOutput;
use Prospecter\Core\PersonalData\Application\PersonCard;
use Prospecter\Core\PersonalData\Application\PossibleDuplicate;

/**
 * Presents what PersonalData shows of a contact: their card, a link per intent the reader may carry out on the person,
 * and the contacts they resemble. One presenter serves one call (ADR 0085).
 */
final class ContactCardPresenter implements ConsultPersonOutput
{
    private const array ROUTES = [
        'Rename' => ['rename', 'api_contacts_rename'],
        'ChangeJobTitle' => ['changeJobTitle', 'api_contacts_change_job_title'],
        'ChangeCivility' => ['changeTitle', 'api_contacts_change_title'],
        'ChangeContactDetails' => ['changeContactDetails', 'api_contacts_change_contact_details'],
        'Forget' => ['forget', 'api_contacts_forget'],
    ];

    public private(set) PersonCard $contact;

    public private(set) ContactProfileLinksResource $links;

    /** @var list<PossibleDuplicateOfContactResource> */
    public private(set) array $possibleDuplicates = [];

    public function __construct(private readonly Links $routes)
    {
    }

    public function present(PersonCard $contact): void
    {
        $this->contact = $contact;
        $this->links = new ContactProfileLinksResource(...$this->possible($contact));
        $this->possibleDuplicates = array_map(fn (PossibleDuplicate $duplicate): PossibleDuplicateOfContactResource => $this->possibleDuplicateOf($contact, $duplicate), $contact->possibleDuplicates);
    }

    /**
     * Names the possible duplicate with a link to them. When the reader may, it adds a link to say, once checked, that
     * the contact is not their duplicate.
     */
    private function possibleDuplicateOf(PersonCard $contact, PossibleDuplicate $duplicate): PossibleDuplicateOfContactResource
    {
        $dismiss = in_array('DismissPossibleDuplicate', $contact->possibleIntents, true) ? $this->routes->to('api_contacts_dismiss_possible_duplicate', ['id' => $contact->id, 'duplicateId' => $duplicate->id]) : null;

        return new PossibleDuplicateOfContactResource(
            $duplicate->id,
            new PersonNameResource($duplicate->firstName, $duplicate->lastName),
            new PossibleDuplicateOfContactLinksResource($this->routes->to('api_contacts_show', ['id' => $duplicate->id]), $dismiss),
        );
    }

    /**
     * @return array<string, LinkResource> by link name
     */
    private function possible(PersonCard $contact): array
    {
        $possible = [];

        foreach ($contact->possibleIntents as $intent) {
            if (isset(self::ROUTES[$intent])) {
                [$name, $route] = self::ROUTES[$intent];
                $possible[$name] = $this->routes->to($route, ['id' => $contact->id]);
            }
        }

        return $possible;
    }
}
