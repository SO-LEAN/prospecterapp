<?php

declare(strict_types=1);

namespace Prospecter\Core\PersonalData\Application\SearchContacts;

use Prospecter\Core\PersonalData\Application\PersonCard;

/**
 * A slice of the contacts of an account, each as the reader may see them.
 */
final readonly class ContactPage
{
    /**
     * @param list<PersonCard> $contacts
     * @param string|null      $next            the cursor of the next slice, null when this one is the last
     * @param list<string>     $possibleIntents names of what the reader may do with the contacts: SearchByText
     */
    public function __construct(
        public array $contacts,
        public ?string $next,
        public array $possibleIntents,
    ) {
    }
}
