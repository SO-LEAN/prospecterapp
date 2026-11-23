<?php

declare(strict_types=1);

namespace Prospecter\Core\Prospecting\Application\SendingNoted;

interface SendingNotedOutput
{
    /**
     * @param string|null $exchangeId none when the message has no fingerprint of its own, because it was logged without its reference
     */
    public function present(?string $exchangeId): void;
}
