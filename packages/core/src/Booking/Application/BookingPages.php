<?php

declare(strict_types=1);

namespace Prospecter\Core\Booking\Application;

use Prospecter\Core\Booking\Domain\Model\BookingPage;
use Prospecter\Core\Booking\Domain\Port\Hosts;
use Prospecter\Core\Booking\Domain\Repository\BookingPageRepository;
use Prospecter\Core\SharedKernel\Domain\MemberId;

/**
 * Finds a host's booking page for the actions on it. The first action sets the page up in the host's account.
 */
final readonly class BookingPages
{
    public function __construct(
        private BookingPageRepository $pages,
        private Hosts $hosts,
    ) {
    }

    public function of(MemberId $host, \DateTimeImmutable $at): BookingPage
    {
        return $this->pages->find($host) ?? BookingPage::setUpFor($host, $this->hosts->accountOf($host), $at);
    }
}
