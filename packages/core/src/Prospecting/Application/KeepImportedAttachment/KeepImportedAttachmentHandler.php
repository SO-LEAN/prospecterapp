<?php

declare(strict_types=1);

namespace Prospecter\Core\Prospecting\Application\KeepImportedAttachment;

use Prospecter\Core\Prospecting\Application\Port\PrivateStorage;
use Prospecter\Core\Prospecting\Domain\Event\AttachmentImported;
use Prospecter\Core\Prospecting\Domain\Exception\UnknownUpload;
use Prospecter\Core\SharedKernel\Application\EventHandler;

/**
 * An imported e-mail brings files to its exchange. Each file's upload is kept once the exchange is written, never
 * before. So the reaction that logs the e-mail still finds its uploads when it is tried again. An exchange that is
 * never written keeps no upload (ADR 0182).
 */
final readonly class KeepImportedAttachmentHandler implements EventHandler
{
    public function __construct(private PrivateStorage $privateStorage)
    {
    }

    /**
     * When the event is delivered again, its upload is already kept, and there is nothing more to keep.
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
