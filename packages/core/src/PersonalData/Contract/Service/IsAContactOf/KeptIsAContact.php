<?php

declare(strict_types=1);

namespace Prospecter\Core\PersonalData\Contract\Service\IsAContactOf;

/**
 * Keeps the answer as it is, for a client that reads it rather than shows it (ADR 0085).
 */
final class KeptIsAContact implements IsAContactOfOutput
{
    public private(set) bool $isAContact;

    public function present(bool $isAContact): void
    {
        $this->isAContact = $isAContact;
    }
}
