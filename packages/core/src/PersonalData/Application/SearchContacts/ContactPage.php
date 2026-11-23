<?php

declare(strict_types=1);

namespace Prospecter\Core\PersonalData\Application\SearchContacts;

use Prospecter\Core\PersonalData\Application\PersonCard;

/**
 * A slice of an account's contacts, each one as the reader may see them.
 */
final readonly class ContactPage
{
    /**
     * @param list<PersonCard> $contacts
     * @param string|null      $next            the next slice's cursor, null when this slice is the last
     * @param list<string>     $possibleIntents the names of what the reader may do with the contacts: SearchByText
     */
    public function __construct(
        public array $contacts,
        public ?string $next,
        public array $possibleIntents,
    ) {
    }
}
