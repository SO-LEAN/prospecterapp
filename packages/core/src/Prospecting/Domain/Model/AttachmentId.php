<?php

declare(strict_types=1);

namespace Prospecter\Core\Prospecting\Domain\Model;

use Prospecter\Core\SharedKernel\Domain\Uuid;

final readonly class AttachmentId extends Uuid
{
    /**
     * The attachment an imported e-mail brings to its exchange is always the same: no client gives the identity of
     * what a reaction imports, and a delivery of the e-mail tried again finds the same attachments (ADR 0182).
     */
    public static function ofImportedEmail(ExchangeId $exchange, string $upload): self
    {
        return self::namedBy(sprintf('%s#%s', $exchange->value, $upload));
    }
}
