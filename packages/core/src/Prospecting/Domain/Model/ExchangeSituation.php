<?php

declare(strict_types=1);

namespace Prospecter\Core\Prospecting\Domain\Model;

/**
 * What decides what an exchange offers to the member who would act on it, at a moment: its files, whether it awaits
 * being made conform, its status, whether the time of a planned exchange has come, and whether the member holds it.
 */
final readonly class ExchangeSituation
{
    public function __construct(
        public int $attachments,
        public bool $awaitsBeingMadeConform,
        public ExchangeStatus $status,
        public bool $due,
        public bool $heldByTheMember,
    ) {
    }
}
