<?php

declare(strict_types=1);

namespace Prospecter\Core\Prospecting\Application\Policy;

use Prospecter\Core\Prospecting\Application\DiscardRemovedAttachment\DiscardRemovedAttachment;
use Prospecter\Core\Prospecting\Domain\Event\AttachmentRemoved;
use Prospecter\Core\SharedKernel\Application\Handler\Policy;

/**
 * When an exchange's attachment is removed, its file leaves the private storage, once the removal is written
 * (ADR 0101).
 */
final readonly class DiscardRemovedAttachmentPolicy implements Policy
{
    public function __invoke(AttachmentRemoved $event): DiscardRemovedAttachment
    {
        return new DiscardRemovedAttachment($event->file, $event->occurredAt);
    }
}
