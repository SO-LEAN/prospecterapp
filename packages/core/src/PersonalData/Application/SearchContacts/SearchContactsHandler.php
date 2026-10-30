<?php

declare(strict_types=1);

namespace Prospecter\Core\PersonalData\Application\SearchContacts;

use Prospecter\Core\PersonalData\Application\Port\ContactPageRetriever;
use Prospecter\Core\PersonalData\Domain\Port\Readers;
use Prospecter\Core\SharedKernel\Application\QueryHandler;
use Prospecter\Core\SharedKernel\Domain\MemberId;
use Prospecter\Core\SharedKernel\Domain\SearchKey;

final readonly class SearchContactsHandler implements QueryHandler
{
    public function __construct(private ContactPageRetriever $contactPageRetriever, private Readers $readers)
    {
    }

    public function __invoke(SearchContacts $query, SearchContactsOutput $output): void
    {
        $reader = $this->readers->readerFrom(new MemberId($query->readerId));
        $reader->ensureMaySearchByText($query->query);

        $output->present($this->contactPageRetriever->sliceFor($reader, SearchKey::of($query->query), $query->after, $query->max));
    }
}
