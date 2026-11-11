<?php

declare(strict_types=1);

namespace Prospecter\Core\Prospecting\Application\DiscardRemovedAttachment;

use Prospecter\Core\Prospecting\Domain\Event\AttachmentRemoved;
use Prospecter\Core\SharedKernel\Application\EventHandler;
use Prospecter\Core\SharedKernel\Application\Port\PrivateStorage;

/**
 * The file of an attachment leaves the private storage once its removal is written, never before: a gesture that
 * fails keeps its attachment whole. Delivered again, it removes nothing more.
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
