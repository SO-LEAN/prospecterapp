<?php

declare(strict_types=1);

namespace Prospecter\Core\Prospecting\Domain\Model;

/**
 * What decides what an exchange offers, at a moment, to the member who would act on it. It holds the exchange's
 * files, whether it awaits redaction, its status, whether a planned exchange's time has come, whether the member
 * holds it, and its channel. A meeting has its place and a video call has its link. Read just after a move, it also
 * holds whether the moment the exchange was moved from is still to come.
 */
final readonly class ExchangeSituation
{
    public function __construct(
        public int $attachments,
        public bool $awaitsBeingMadeConform,
        public ExchangeStatus $status,
        public bool $due,
        public bool $heldByTheMember,
        public Channel $channel,
        public bool $formerMomentToCome = false,
    ) {
    }
}
