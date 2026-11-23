<?php

declare(strict_types=1);

namespace Prospecter\Core\Prospecting\Domain\Model;

use Prospecter\Core\SharedKernel\Domain\Identity\Uuid;

final readonly class AttachmentId extends Uuid
{
    /**
     * An imported e-mail always brings the same attachment to its exchange. No client gives the identity of what a
     * reaction imports, and a retried delivery of the e-mail finds the same attachments (ADR 0182).
     */
    public static function ofImportedEmail(ExchangeId $exchange, string $upload): self
    {
        return self::namedBy(sprintf('%s#%s', $exchange->value, $upload));
    }
}
