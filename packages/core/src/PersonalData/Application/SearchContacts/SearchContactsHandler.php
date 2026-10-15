<?php

declare(strict_types=1);

namespace Prospecter\Core\PersonalData\Application\SearchContacts;

use Prospecter\Core\PersonalData\Domain\PersonRepository;
use Prospecter\Core\PersonalData\Domain\Purpose;
use Prospecter\Core\PersonalData\Domain\ReaderService;
use Prospecter\Core\SharedKernel\Application\QueryHandler;
use Prospecter\Core\SharedKernel\Domain\MemberId;

final readonly class SearchContactsHandler implements QueryHandler
{
    public function __construct(private PersonRepository $persons, private ReaderService $readerService)
    {
    }

    public function __invoke(SearchContacts $query): SearchContactsResponse
    {
        $reader = $this->readerService->readerFrom(new MemberId($query->readerId));
        $reader->ensureMaySearchByText($query->query);

        return SearchContactsResponse::of($this->persons->pageOf($reader->accountId, Purpose::Prospecting, $query->query, $query->page, $query->maxByPage), $reader);
    }
}
