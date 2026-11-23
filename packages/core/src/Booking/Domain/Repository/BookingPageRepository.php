<?php

declare(strict_types=1);

namespace Prospecter\Core\Booking\Domain\Repository;

use Prospecter\Core\Booking\Domain\Exception\UnknownBookingPage;
use Prospecter\Core\Booking\Domain\Model\BookingCode;
use Prospecter\Core\Booking\Domain\Model\BookingPage;
use Prospecter\Core\SharedKernel\Domain\Identity\MemberId;

interface BookingPageRepository
{
    /**
     * @throws UnknownBookingPage
     */
    public function get(MemberId $host): BookingPage;

    public function find(MemberId $host): ?BookingPage;

    /**
     * The page that this code's link currently leads to. A former code leads to no page.
     */
    public function findByCode(BookingCode $code): ?BookingPage;

    /**
     * Saves the page and links its code to it. The link of a former code goes away.
     */
    public function save(BookingPage $page): void;
}
