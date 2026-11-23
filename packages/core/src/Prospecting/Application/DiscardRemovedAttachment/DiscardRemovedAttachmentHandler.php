<?php

declare(strict_types=1);

namespace Prospecter\Core\Prospecting\Application\DiscardRemovedAttachment;

use Prospecter\Core\Prospecting\Application\Port\PrivateStorage;
use Prospecter\Core\Prospecting\Domain\Event\AttachmentRemoved;
use Prospecter\Core\SharedKernel\Application\Handler\EventHandler;

/**
 * Removes an attachment's file from the private storage once the removal is written, never before. So a gesture
 * that fails keeps its attachment whole. When the event is delivered again, it removes nothing more.
 */
final readonly class DiscardRemovedAttachmentHandler implements EventHandler
{
    public function __construct(private PrivateStorage $privateStorage)
    {
    }

    public function __invoke(AttachmentRemoved $event): void
    {
        $this->privateStorage->remove($event->file);
    }
}
