<?php

declare(strict_types=1);

namespace Prospecter\Core\PersonalData\Application\Person\ConsultPersons;

use Prospecter\Core\PersonalData\Application\Port\PersonCardRetriever;
use Prospecter\Core\PersonalData\Domain\Model\PersonId;
use Prospecter\Core\PersonalData\Domain\Port\Readers;
use Prospecter\Core\SharedKernel\Application\Handler\QueryHandler;
use Prospecter\Core\SharedKernel\Domain\Identity\MemberId;

final readonly class ConsultPersonsHandler implements QueryHandler
{
    public function __construct(private PersonCardRetriever $personCardRetriever, private Readers $readers)
    {
    }

    public function __invoke(ConsultPersons $query, ConsultPersonsOutput $output): void
    {
        $reader = $this->readers->readerFrom(new MemberId($query->readerId));
        $cards = $this->personCardRetriever->ofIds(array_map(static fn (string $id): PersonId => new PersonId($id), $query->ids), $reader);

        $output->present(array_column($cards, null, 'id'));
    }
}
