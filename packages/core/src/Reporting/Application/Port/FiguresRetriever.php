<?php

declare(strict_types=1);

namespace Prospecter\Core\Reporting\Application\Port;

use Prospecter\Core\Reporting\Application\ConsultFigures\FiguresResponse;
use Prospecter\Core\Reporting\Domain\Model\Month;
use Prospecter\Core\SharedKernel\Domain\AccountId;

/**
 * Reads the figures the facts of a version made (ADR 0088).
 */
interface FiguresRetriever
{
    /**
     * The figures of the account, its exchanges of the month; zeros for an account without facts.
     */
    public function of(int $version, AccountId $accountId, Month $month): FiguresResponse;
}
