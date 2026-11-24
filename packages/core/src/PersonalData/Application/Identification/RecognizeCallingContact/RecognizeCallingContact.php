<?php

declare(strict_types=1);

namespace Prospecter\Core\PersonalData\Application\Identification\RecognizeCallingContact;

/**
 * Finds which of my contacts has the number that is calling me.
 */
final readonly class RecognizeCallingContact
{
    public function __construct(
        public string $readerId,
        public string $phoneNumber,
    ) {
    }
}
