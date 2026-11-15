<?php

declare(strict_types=1);

namespace Prospecter\Core\Prospecting\Contract\Service\ConsultHeldExchanges;

/**
 * The published language of an exchange as its holder holds it (ADR 0045, 0127): its holder, its status (`planned`,
 * `done`, `missed`, `cancelled`), its channel (`call`, `meeting`, `videoCall`...), its moment and how long it lasts
 * when it was planned, the place of a meeting or the link of a video call, its contact, none when it is with the
 * organization itself, and the first organization it is kept for, with its name.
 */
final readonly class HeldExchangeResponse
{
    public function __construct(
        public string $exchangeId,
        public string $holderId,
        public string $status,
        public string $channel,
        public \DateTimeImmutable $startsAt,
        public ?int $durationInMinutes,
        public ?string $place,
        public ?string $link,
        public ?string $contactId,
        public string $organizationId,
        public string $organizationName,
    ) {
    }
}
