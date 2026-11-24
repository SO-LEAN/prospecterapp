<?php

declare(strict_types=1);

namespace Prospecter\Core\Prospecting\Application\Attachment\DownloadAttachment;

use Prospecter\Core\SharedKernel\Application\Handler\OutputPort;

interface DownloadAttachmentOutput extends OutputPort
{
    public function present(AttachmentDownloadResponse $download): void;
}
