<?php

declare(strict_types=1);

namespace Prospecter\Core\PersonalData\Application\IdentifyBooker;

final readonly class IdentifyBooker
{
    /**
     * @param string $bookerId the identity given to the contact to attach when no account contact has the booker's e-mail
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
