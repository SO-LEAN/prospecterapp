<?php

declare(strict_types=1);

namespace App\Reporting\Persistence;

use App\Shared\Persistence\Table;
use App\Shared\Typed;
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
        $total = Typed::document($items[ReportingKeys::TOTAL] ?? []);
        $ofTheMonth = Typed::document($items[ReportingKeys::month($month)] ?? []);

        return new FiguresResponse(
            Typed::int($total['activeOrganizations'] ?? 0),
            Typed::int($total['locatedOrganizations'] ?? 0),
            Typed::int($total['contacts'] ?? 0),
            Typed::int($total[ReportingKeys::EXCHANGES] ?? 0),
            Typed::int($ofTheMonth[ReportingKeys::EXCHANGES] ?? 0),
        );
    }
}
