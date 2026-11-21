<?php

declare(strict_types=1);

namespace Prospecter\Core\Prospecting\Application\KeepImportedAttachment;

use Prospecter\Core\Prospecting\Domain\Event\AttachmentImported;
use Prospecter\Core\SharedKernel\Application\EventHandler;
use Prospecter\Core\SharedKernel\Application\Port\PrivateStorage;
use Prospecter\Core\SharedKernel\Domain\Exception\UnknownUpload;

/**
 * The upload of a file an imported e-mail brought to its exchange is kept once the exchange is written, never before:
 * the reaction noting the e-mail, tried again, finds its uploads, and an exchange never written keeps none (ADR 0182).
 */
final readonly class KeepImportedAttachmentHandler implements EventHandler
{
    public function __construct(private PrivateStorage $privateStorage)
    {
    }

    /**
     * Delivered again, its upload is kept already: nothing more to keep.
     */
    public function __invoke(AttachmentImported $event): void
    {
        try {
            $this->privateStorage->keep($event->file);
        } catch (UnknownUpload) {
            return;
        }
    }
}
