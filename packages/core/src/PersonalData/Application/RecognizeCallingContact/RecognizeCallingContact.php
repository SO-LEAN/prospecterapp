<?php

declare(strict_types=1);

namespace Prospecter\Core\PersonalData\Application\RecognizeCallingContact;

/**
 * Who of my contacts has the number calling me.
 */
final readonly class RecognizeCallingContact
{
    public function __construct(
        public string $readerId,
        public string $phoneNumber,
    ) {
    }
}
