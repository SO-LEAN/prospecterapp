<?php

declare(strict_types=1);

namespace Prospecter\Core\PersonalData\Application\ConsultPerson;

use Prospecter\Core\PersonalData\Application\Port\PersonCardRetriever;
use Prospecter\Core\PersonalData\Domain\Port\Readers;
use Prospecter\Core\SharedKernel\Application\QueryHandler;
use Prospecter\Core\SharedKernel\Domain\MemberId;
use Prospecter\Core\SharedKernel\Domain\PersonId;

final readonly class ConsultPersonHandler implements QueryHandler
{
    public function __construct(private PersonCardRetriever $personCardRetriever, private Readers $readers)
    {
    }

    public function __invoke(ConsultPerson $query, ConsultPersonOutput $output): void
    {
        $output->present($this->personCardRetriever->get(new PersonId($query->id), $this->readers->readerFrom(new MemberId($query->readerId))));
    }
}
