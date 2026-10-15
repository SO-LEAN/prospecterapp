<?php

declare(strict_types=1);

namespace Prospecter\Core\PersonalData\Application\SearchContacts;

use Prospecter\Core\PersonalData\Application\PersonResponse;
use Prospecter\Core\PersonalData\Domain\ContactsIntent;
use Prospecter\Core\PersonalData\Domain\Person;
use Prospecter\Core\PersonalData\Domain\Reader;
use Prospecter\Core\SharedKernel\Domain\Page;

final readonly class SearchContactsResponse
{
    /**
     * @param list<PersonResponse> $contacts
     * @param list<string>         $possibleIntents names of what the reader may do with the contacts: Collect, SearchByText
     */
    private function __construct(
        public int $currentPage,
        public int $total,
        public int $totalPages,
        public array $contacts,
        public array $possibleIntents,
    ) {
    }

    /**
     * @param Page<Person> $page
     */
    public static function of(Page $page, Reader $reader): self
    {
        return new self(
            $page->number,
            $page->total,
            $page->totalPages,
            array_map(static fn (Person $contact): PersonResponse => PersonResponse::of($contact, $reader), $page->content),
            array_map(static fn (ContactsIntent $intent): string => $intent->name, $reader->contactsIntents()),
        );
    }
}
