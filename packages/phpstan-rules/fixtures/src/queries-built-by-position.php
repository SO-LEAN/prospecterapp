<?php

declare(strict_types=1);

use Prospecter\Core\Prospecting\Application\ConsultTimeline\ConsultTimeline;

$now = new DateTimeImmutable('2026-11-22');

new ConsultTimeline('prospector', 'person', $now, 'after');
