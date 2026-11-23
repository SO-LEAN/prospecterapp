<?php

declare(strict_types=1);

namespace Tests\Core\Double;

use Prospecter\Core\PersonalData\Application\PersonCard;
use Prospecter\Core\PersonalData\Application\Port\ContactPageRetriever;
use Prospecter\Core\PersonalData\Application\SearchContacts\ContactPage;
use Prospecter\Core\PersonalData\Domain\Model\ContactsIntent;
use Prospecter\Core\PersonalData\Domain\Model\Person;
use Prospecter\Core\PersonalData\Domain\Model\Purpose;
use Prospecter\Core\PersonalData\Domain\Model\Reader;
use Prospecter\Core\SharedKernel\Domain\Search\SearchKey;
use Prospecter\Core\SharedKernel\Domain\Search\SearchKeys;

/**
 * Reads the contacts that the in-memory repository saved, as the adapter reads the table's items.
 */
final readonly class InMemoryContactPageRetriever implements ContactPageRetriever
{
    public function __construct(private InMemoryDatabase $database)
    {
    }

    public function sliceFor(Reader $reader, ?SearchKey $search, ?string $after, int $max): ContactPage
    {
        $contacts = array_values(array_filter($this->database->persons(), static fn (Person $person): bool => $person->accountId()->equals($reader->accountId) && Purpose::Prospecting === $person->purpose()));
        $slice = NewestFirst::slice($contacts, static fn (Person $person): string => (string) $person->id(), static fn (Person $person): SearchKeys => self::searchKeysOf($person, $reader), $search, $after, $max);

        return new ContactPage(
            array_map(static fn (Person $contact): PersonCard => InMemoryPersonCardRetriever::cardOf($contact, $reader), $slice->content),
            $slice->next,
            array_map(static fn (ContactsIntent $intent): string => $intent->name, $reader->contactsIntents()),
        );
    }

    /**
     * The adapter reads the names' index alone (last and first names) for a reader who does not search the emails.
     */
    private static function searchKeysOf(Person $person, Reader $reader): SearchKeys
    {
        $keys = $person->searchKeys();

        return $reader->searchesTheEmails() ? $keys : new SearchKeys($keys->name?->value, null, $keys->firstName?->value);
    }
}
