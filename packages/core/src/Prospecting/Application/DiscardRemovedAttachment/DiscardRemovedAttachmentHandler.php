<?php

declare(strict_types=1);

namespace Prospecter\Core\Prospecting\Application\DiscardRemovedAttachment;

use Prospecter\Core\Prospecting\Application\Port\PrivateStorage;
use Prospecter\Core\SharedKernel\Application\Handler\CommandHandler;

/**
 * Removes an attachment's file from the private storage once the removal is written, never before. So a gesture
 * that fails keeps its attachment whole. When the command comes again, it removes nothing more.
 */
final readonly class DiscardRemovedAttachmentHandler implements CommandHandler
{
    public function __construct(private PrivateStorage $privateStorage)
    {
    }

    public function __invoke(DiscardRemovedAttachment $command): void
    {
        $this->privateStorage->remove($command->file);
    }
}
