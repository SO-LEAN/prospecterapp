<?php

declare(strict_types=1);

namespace Prospecter\Core\Prospecting\Application\Policy;

use Prospecter\Core\Prospecting\Application\Attachment\KeepImportedAttachment\KeepImportedAttachment;
use Prospecter\Core\Prospecting\Domain\Event\AttachmentImported;
use Prospecter\Core\SharedKernel\Application\Handler\Policy;

/**
 * When an imported e-mail brings a file to its exchange, the file's upload is kept, once the exchange is written
 * (ADR 0182).
 */
final readonly class KeepImportedAttachmentPolicy implements Policy
{
    public function __invoke(AttachmentImported $event): KeepImportedAttachment
    {
        return new KeepImportedAttachment($event->file, $event->occurredAt);
    }
}
