<?php

namespace App\Reporting\Persistence;

use App\Shared\Persistence\Table;
use Prospecter\Core\Reporting\Application\ConsultFigures\FiguresResponse;
use Prospecter\Core\Reporting\Application\Port\FiguresRetriever;
use Prospecter\Core\Reporting\Domain\Model\Month;
use Prospecter\Core\SharedKernel\Domain\AccountId;

/**
 * Reads the figures of an account in a version from the items `DynamoFactRepository` adds to (ADR 0088): its total
 * and its month, zeros for those never written.
 */
final readonly class DynamoFiguresRetriever implements FiguresRetriever
{
    public function __construct(private Table $reportingTable)
    {
    }

    public function of(int $version, AccountId $accountId, Month $month): FiguresResponse
    {
        $figures = ReportingKeys::figures($version, $accountId->value);
        $items = array_column($this->reportingTable->items([[$figures, ReportingKeys::TOTAL], [$figures, ReportingKeys::month($month)]]), null, 'SK');
        $total = $items[ReportingKeys::TOTAL] ?? [];

        return new FiguresResponse(
            (int) ($total['activeOrganizations'] ?? 0),
            (int) ($total['locatedOrganizations'] ?? 0),
            (int) ($total['contacts'] ?? 0),
            (int) ($total[ReportingKeys::EXCHANGES] ?? 0),
            (int) ($items[ReportingKeys::month($month)][ReportingKeys::EXCHANGES] ?? 0),
        );
    }
}
