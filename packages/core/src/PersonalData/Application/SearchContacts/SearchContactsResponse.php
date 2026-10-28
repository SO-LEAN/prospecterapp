<?php

declare(strict_types=1);

namespace Prospecter\Core\PersonalData\Application\SearchContacts;

use Prospecter\Core\PersonalData\Application\PersonResponse;
use Prospecter\Core\PersonalData\Domain\Model\ContactsIntent;
use Prospecter\Core\PersonalData\Domain\Model\Person;
use Prospecter\Core\PersonalData\Domain\Model\Reader;
use Prospecter\Core\SharedKernel\Domain\Slice;

final readonly class SearchContactsResponse
{
    /**
     * @param list<PersonResponse> $contacts
     * @param string|null          $next            the cursor of the next slice, null when this one is the last
     * @param list<string>         $possibleIntents names of what the reader may do with the contacts: SearchByText
     */
    private function __construct(
        public array $contacts,
        public ?string $next,
        public array $possibleIntents,
    ) {
    }

    /**
     * @param Slice<Person> $slice
     */
    public static function of(Slice $slice, Reader $reader): self
    {
        return new self(
            array_map(static fn (Person $contact): PersonResponse => PersonResponse::of($contact, $reader), $slice->content),
            $slice->next,
            array_map(static fn (ContactsIntent $intent): string => $intent->name, $reader->contactsIntents()),
        );
    }
}
