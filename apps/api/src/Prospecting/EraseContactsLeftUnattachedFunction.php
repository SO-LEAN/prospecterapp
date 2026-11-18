<?php

namespace App\Prospecting;

use Bref\Context\Context;
use Bref\Event\Handler;

/**
 * The function the schedule of an instance calls every hour (ADR 0147): it erases the contacts left unattached
 * (ADR 0146), whatever the event of the schedule says.
 */
final readonly class EraseContactsLeftUnattachedFunction implements Handler
{
    public function __construct(private ContactsLeftUnattachedEraser $eraser)
    {
    }

    /**
     * @return array{erased: int}
     */
    public function handle(mixed $event, Context $context): array
    {
        return ['erased' => $this->eraser->erase()];
    }
}
