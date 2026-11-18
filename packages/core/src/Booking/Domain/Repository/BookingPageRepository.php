<?php

declare(strict_types=1);

namespace Prospecter\Core\Booking\Domain\Repository;

use Prospecter\Core\Booking\Domain\Exception\UnknownBookingPage;
use Prospecter\Core\Booking\Domain\Model\BookingPage;
use Prospecter\Core\SharedKernel\Domain\MemberId;

interface BookingPageRepository
{
    /**
     * @throws UnknownBookingPage
     */
    public function get(MemberId $host): BookingPage;

    public function find(MemberId $host): ?BookingPage;

    /**
     * Keeps the page and the link of its code to it, the link of a former code gone with it.
     */
    public function save(BookingPage $page): void;
}
