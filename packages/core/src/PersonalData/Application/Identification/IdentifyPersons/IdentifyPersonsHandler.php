<?php

declare(strict_types=1);

namespace Prospecter\Core\PersonalData\Application\Identification\IdentifyPersons;

use Prospecter\Core\PersonalData\Application\Identification\Identification;
use Prospecter\Core\PersonalData\Application\Identification\PersonClues;
use Prospecter\Core\PersonalData\Application\Identification\PersonIdentifier;
use Prospecter\Core\PersonalData\Domain\Port\Readers;
use Prospecter\Core\SharedKernel\Application\Handler\QueryHandler;
use Prospecter\Core\SharedKernel\Domain\Identity\MemberId;

final readonly class IdentifyPersonsHandler implements QueryHandler
{
    public function __construct(
        private PersonIdentifier $personIdentifier,
        private Readers $readers,
    ) {
    }

    public function __invoke(IdentifyPersons $query, IdentifyPersonsOutput $output): void
    {
        $reader = $this->readers->readerFrom(new MemberId($query->readerId));
        $output->present(array_map(fn (PersonClues $clues): Identification => $this->personIdentifier->identify($reader, $clues), $query->clues));
    }
}
