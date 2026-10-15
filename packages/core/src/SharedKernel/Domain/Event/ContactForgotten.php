<?php

declare(strict_types=1);

namespace Prospecter\Core\SharedKernel\Domain\Event;

/**
 * A contact was erased: no organization may name it any more.
 */
final readonly class ContactForgotten extends PersonEvent
{
}
