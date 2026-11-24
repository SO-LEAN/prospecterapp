<?php

declare(strict_types=1);

use Prospecter\Core\Prospecting\Application\Organization\ChangeContactDetails\ChangeContactDetails;

$now = new DateTimeImmutable('2026-11-22');

new ChangeContactDetails('prospector', 'organization', 'contact@example.com', '0102030405', 'fr', $now);
new ChangeContactDetails('prospector', 'organization', email: 'contact@example.com', phoneNumber: '0102030405', language: 'fr', executedAt: $now);
