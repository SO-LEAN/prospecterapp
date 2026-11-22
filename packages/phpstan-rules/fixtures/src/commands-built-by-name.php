<?php

declare(strict_types=1);

use Prospecter\Core\Prospecting\Application\ChangeContactDetails\ChangeContactDetails;

$now = new DateTimeImmutable('2026-11-22');

new ChangeContactDetails(prospectorId: 'prospector', id: 'organization', email: 'contact@example.com', phoneNumber: '0102030405', language: 'fr', executedAt: $now);
