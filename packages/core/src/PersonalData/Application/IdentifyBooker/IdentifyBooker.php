<?php

declare(strict_types=1);

namespace Prospecter\Core\PersonalData\Application\IdentifyBooker;

final readonly class IdentifyBooker
{
    /**
     * @param string $bookerId the identity of the contact to attach collected when nobody has the booker's e-mail
     */
    public function __construct(
        public string $appointmentId,
        public string $accountId,
        public string $hostId,
        public string $bookerId,
        public \DateTimeImmutable $executedAt,
    ) {
    }
}
