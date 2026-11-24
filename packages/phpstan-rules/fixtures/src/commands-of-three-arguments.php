<?php

declare(strict_types=1);

use Prospecter\Core\Booking\Application\BookingPage\ChangeBookingLink\ChangeBookingLink;
use Prospecter\Core\Prospecting\Application\Timeline\ConsultTimeline\ConsultTimeline;

$now = new DateTimeImmutable('2026-11-22');

new ChangeBookingLink('member', 'code', $now);
new ConsultTimeline('prospector', 'person', $now);
