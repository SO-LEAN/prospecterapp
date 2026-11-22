<?php

declare(strict_types=1);

$now = new DateTimeImmutable('2026-11-22');

new DatePeriod($now, new DateInterval('P1D'), 3, DatePeriod::EXCLUDE_START_DATE);
