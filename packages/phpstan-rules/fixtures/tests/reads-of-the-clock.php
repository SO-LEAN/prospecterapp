<?php

declare(strict_types=1);

use Symfony\Component\Clock\Clock;
use Symfony\Component\Clock\MockClock;
use Symfony\Component\Clock\NativeClock;

new DateTimeImmutable();
new \DateTime();
new DateTimeImmutable('now');
new DateTimeImmutable('+7 days');
new DateTimeImmutable('tomorrow');
time();
microtime(true);
date('Y-m-d');
strtotime('+1 hour');
mktime();
new NativeClock();
new MockClock();
Clock::get();

new DateTimeImmutable('2026-10-15 09:00 UTC');
new DateTimeImmutable('2026-10-15 09:00 UTC +7 days');
new DateTimeImmutable('@1760518800');
new DateTimeImmutable('2026-10-15', new DateTimeZone('Europe/Paris'));
date('Y-m-d', 1760518800);
strtotime('+1 hour', 1760518800);
mktime(9, 0, 0, 10, 15, 2026);
new MockClock('2026-10-15 09:00 UTC');
new DateTimeImmutable($moment ?? '2026-10-15');
// @phpstan-ignore prospecter.realClock (Cognito issues its tokens on the real clock)
time();
