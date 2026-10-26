<?php

declare(strict_types=1);

namespace Prospecter\Core\Prospecting\Application\DownloadAttachment;

use Prospecter\Core\SharedKernel\Application\OutputPort;

interface DownloadAttachmentOutput extends OutputPort
{
    public function present(AttachmentDownloadResponse $download): void;
}
