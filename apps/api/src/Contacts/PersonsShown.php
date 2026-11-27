<?php

declare(strict_types=1);

namespace App\Contacts;

use App\Exchanges\Resource\AuthorResource;
use App\PersonalData\Resource\PersonNameResource;
use Prospecter\Core\PersonalData\Application\PersonCard;

/**
 * The persons a view shows, as PersonalData lets its reader read them (ADR 0217): its contacts by their ids, and its
 * members by their member ids. A person PersonalData no longer reads, or a member Accounts no longer knows, is absent.
 */
final readonly class PersonsShown
{
    /**
     * @param array<string, PersonCard> $contacts by person id
     * @param array<string, PersonCard> $members  by member id
     */
    public function __construct(
        public array $contacts = [],
        public array $members = [],
    ) {
    }

    public function contact(string $id): ?PersonCard
    {
        return $this->contacts[$id] ?? null;
    }

    public function nameOfContact(string $id): PersonNameResource
    {
        return self::nameOf($this->contact($id));
    }

    /**
     * A member who acted, by their name and e-mail. A member no longer known has neither.
     */
    public function author(string $memberId): AuthorResource
    {
        $member = $this->members[$memberId] ?? null;

        return new AuthorResource(self::nameOf($member), $member?->email);
    }

    private static function nameOf(?PersonCard $person): PersonNameResource
    {
        return new PersonNameResource($person?->firstName, $person?->lastName);
    }
}
