<?php

declare(strict_types=1);

namespace Prospecter\Core\Prospecting\Application\Attachment\KeepImportedAttachment;

final readonly class KeepImportedAttachment
{
    /**
     * @param string $upload the upload of the imported e-mail's file in the private storage
     */
    public function __construct(
        public string $upload,
        public \DateTimeImmutable $executedAt,
    ) {
    }
}
