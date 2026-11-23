<?php

declare(strict_types=1);

namespace Prospecter\Core\Prospecting\Domain\Exception;

use Prospecter\Core\SharedKernel\Domain\Exception\NotFound;

/**
 * No file was sent under this upload, or the upload expired, or its file is already kept.
 */
final class UnknownUpload extends NotFound
{
    public static function identifiedBy(string $upload): self
    {
        return new self(sprintf('Upload %s not found', $upload));
    }
}
