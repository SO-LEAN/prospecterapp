<?php

declare(strict_types=1);

namespace Prospecter\Core\Prospecting\Domain\Exception;

use Prospecter\Core\Prospecting\Domain\Model\AttachmentId;
use Prospecter\Core\SharedKernel\Domain\Exception\NotFound;

final class UnknownAttachment extends NotFound
{
    public static function identifiedBy(AttachmentId $id): self
    {
        return new self(sprintf('Attachment %s not found', $id));
    }
}
