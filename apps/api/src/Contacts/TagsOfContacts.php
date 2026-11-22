<?php

declare(strict_types=1);

namespace App\Contacts;

use Prospecter\Core\Campaigns\Application\ConsultContactTags\ConsultContactTags;
use Prospecter\Core\Campaigns\Application\ConsultContactTags\ContactTagsResponse;
use Prospecter\Core\Campaigns\Application\ConsultContactTags\KeptContactTags;
use Prospecter\Core\SharedKernel\Application\QueryHandler;

/**
 * The tags contacts carry, their own, inherited and automatic together, read in Campaigns in one batch (ADR 0141).
 */
final readonly class TagsOfContacts
{
    public function __construct(private QueryHandler $consultContactTags)
    {
    }

    /**
     * @param list<string> $contactIds
     *
     * @return array<string, list<string>> by contact id, in the order of the names of the tags
     */
    public function read(string $reader, array $contactIds): array
    {
        $kept = new KeptContactTags();
        $this->consultContactTags->__invoke(new ConsultContactTags($reader, $contactIds), $kept);

        return array_column(array_map(static fn (ContactTagsResponse $contact): array => [$contact->contactId, $contact->carried()], $kept->contacts), 1, 0);
    }
}
