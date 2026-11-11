<?php

declare(strict_types=1);

namespace Tests\Core\Double;

use Prospecter\Core\Reporting\Application\ConsultFigures\FiguresResponse;
use Prospecter\Core\Reporting\Application\Port\FiguresRetriever;
use Prospecter\Core\Reporting\Domain\Model\Month;
use Prospecter\Core\SharedKernel\Domain\AccountId;

/**
 * Reads the counters the in-memory repository of the facts wrote, as the adapter reads the items of the figures.
 */
final readonly class InMemoryFiguresRetriever implements FiguresRetriever
{
    public function __construct(private InMemoryDatabase $database)
    {
    }

    public function of(int $version, AccountId $accountId, Month $month): FiguresResponse
    {
        $figure = fn (string $name): int => $this->database->figure($version, $accountId->value, $name);

        return new FiguresResponse(
            $figure(InMemoryFactRepository::ACTIVE_ORGANIZATIONS),
            $figure(InMemoryFactRepository::LOCATED_ORGANIZATIONS),
            $figure(InMemoryFactRepository::CONTACTS),
            $figure(InMemoryFactRepository::EXCHANGES),
            $figure(InMemoryFactRepository::exchangesOf($month->value)),
        );
    }
}
