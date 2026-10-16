<?php

declare(strict_types=1);

namespace Prospecter\Core\PersonalData\Application\SearchContacts;

use Prospecter\Core\PersonalData\Domain\PersonRepository;
use Prospecter\Core\PersonalData\Domain\Purpose;
use Prospecter\Core\PersonalData\Domain\ReaderService;
use Prospecter\Core\SharedKernel\Application\QueryHandler;
use Prospecter\Core\SharedKernel\Domain\MemberId;
use Prospecter\Core\SharedKernel\Domain\SearchTerms;

final readonly class SearchContactsHandler implements QueryHandler
{
    public function __construct(private PersonRepository $persons, private ReaderService $readerService)
    {
    }

    public function __invoke(SearchContacts $query): SearchContactsResponse
    {
        $reader = $this->readerService->readerFrom(new MemberId($query->readerId));
        $reader->ensureMaySearchByText($query->query);

        return SearchContactsResponse::of($this->persons->sliceOf($reader->accountId, Purpose::Prospecting, SearchTerms::of($query->query), $query->after, $query->max), $reader);
    }
}
