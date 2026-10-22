<?php

declare(strict_types=1);

namespace Prospecter\Core\SharedKernel\Domain\Exception;

/**
 * No file was sent under this upload, or it expired, or it is already kept.
 */
final class UnknownUpload extends NotFound
{
    public static function identifiedBy(string $upload): self
    {
        return new self(sprintf('Upload %s not found', $upload));
    }
}
