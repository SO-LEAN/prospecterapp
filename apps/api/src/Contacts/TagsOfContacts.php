<?php

declare(strict_types=1);

namespace App\Contacts;

use Prospecter\Core\Campaigns\Application\Tagging\ConsultContactTags\ConsultContactTags;
use Prospecter\Core\Campaigns\Application\Tagging\ConsultContactTags\ContactTagsResponse;
use Prospecter\Core\Campaigns\Application\Tagging\ConsultContactTags\KeptContactTags;
use Prospecter\Core\SharedKernel\Application\Handler\QueryHandler;

/**
 * Reads the tags contacts carry in Campaigns, in one batch: their own tags, the inherited ones and the automatic ones
 * together (ADR 0141).
 */
final readonly class TagsOfContacts
{
    public function __construct(private QueryHandler $consultContactTags)
    {
    }

    /**
     * @param list<string> $contactIds
     *
     * @return array<string, list<string>> by contact id, ordered by tag name
     */
    public function read(string $reader, array $contactIds): array
    {
        $kept = new KeptContactTags();
        $this->consultContactTags->__invoke(new ConsultContactTags($reader, $contactIds), $kept);

        return array_column(array_map(static fn (ContactTagsResponse $contact): array => [$contact->contactId, $contact->carried()], $kept->contacts), 1, 0);
    }
}
