<?php

declare(strict_types=1);

namespace Prospecter\Core\Booking\Application\ConsultPublicBookingPage;

use Prospecter\Core\Booking\Application\Port\PublicBookingPageRetriever;
use Prospecter\Core\Booking\Domain\Exception\UnknownBookingPage;
use Prospecter\Core\SharedKernel\Application\QueryHandler;

final readonly class ConsultPublicBookingPageHandler implements QueryHandler
{
    public function __construct(private PublicBookingPageRetriever $pages)
    {
    }

    public function __invoke(ConsultPublicBookingPage $query, ConsultPublicBookingPageOutput $output): void
    {
        $output->present($this->pages->byCode($query->code) ?? throw UnknownBookingPage::withCode($query->code));
    }
}
