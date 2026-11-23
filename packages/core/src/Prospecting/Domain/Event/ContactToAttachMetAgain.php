<?php

declare(strict_types=1);

namespace Prospecter\Core\Prospecting\Domain\Event;

/**
 * A contact to attach has a meeting later than their last one. Their erasure is pushed back.
 */
final readonly class ContactToAttachMetAgain extends ContactToAttachEvent
{
}
