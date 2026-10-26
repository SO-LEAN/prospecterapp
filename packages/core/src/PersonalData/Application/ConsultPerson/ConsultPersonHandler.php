<?php

declare(strict_types=1);

namespace Prospecter\Core\PersonalData\Application\ConsultPerson;

use Prospecter\Core\PersonalData\Application\PersonResponse;
use Prospecter\Core\PersonalData\Domain\PersonRepository;
use Prospecter\Core\PersonalData\Domain\ReaderService;
use Prospecter\Core\SharedKernel\Application\QueryHandler;
use Prospecter\Core\SharedKernel\Domain\MemberId;
use Prospecter\Core\SharedKernel\Domain\PersonId;

final readonly class ConsultPersonHandler implements QueryHandler
{
    public function __construct(private PersonRepository $persons, private ReaderService $readerService)
    {
    }

    public function __invoke(ConsultPerson $query, ConsultPersonOutput $output): void
    {
        $output->present(PersonResponse::of($this->persons->get(new PersonId($query->id)), $this->readerService->readerFrom(new MemberId($query->readerId))));
    }
}
