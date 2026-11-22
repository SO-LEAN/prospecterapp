<?php

declare(strict_types=1);

namespace Prospecter\Core\Prospecting\Application\SendingNoted;

final class KeptSendingNoted implements SendingNotedOutput
{
    public private(set) ?string $exchangeId = null;

    public function present(?string $exchangeId): void
    {
        $this->exchangeId = $exchangeId;
    }
}
