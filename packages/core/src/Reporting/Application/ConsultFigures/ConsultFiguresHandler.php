<?php

declare(strict_types=1);

namespace Prospecter\Core\Reporting\Application\ConsultFigures;

use Prospecter\Core\Reporting\Application\Port\FiguresRetriever;
use Prospecter\Core\Reporting\Application\Port\ProjectionVersions;
use Prospecter\Core\Reporting\Domain\Model\Month;
use Prospecter\Core\Reporting\Domain\Port\SourceAccounts;
use Prospecter\Core\SharedKernel\Application\Handler\QueryHandler;
use Prospecter\Core\SharedKernel\Domain\Identity\MemberId;

/**
 * Presents the member's account figures from the active version. Before the first rebuild, every figure is zero.
 */
final readonly class ConsultFiguresHandler implements QueryHandler
{
    public function __construct(
        private SourceAccounts $sourceAccounts,
        private ProjectionVersions $versions,
        private FiguresRetriever $figuresRetriever,
    ) {
    }

    public function __invoke(ConsultFigures $query, ConsultFiguresOutput $output): void
    {
        $accountId = $this->sourceAccounts->accountOf(new MemberId($query->memberId));
        $version = $this->versions->active();

        $output->present(null === $version ? FiguresResponse::zero() : $this->figuresRetriever->of($version, $accountId, Month::of($query->at)));
    }
}
