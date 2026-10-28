<?php

declare(strict_types=1);

namespace Prospecter\Core\Prospecting\Application;

use Prospecter\Core\Prospecting\Domain\Model\Exchange;
use Prospecter\Core\SharedKernel\Application\Port\PrivateStorage;

/**
 * The files joined to an exchange go with it: they may hold personal data (ADR 0077).
 */
final readonly class AttachedFiles
{
    public function __construct(private PrivateStorage $privateStorage)
    {
    }

    public function forgetThoseOf(Exchange $erased): void
    {
        foreach ($erased->attachments() as $attachment) {
            $this->privateStorage->remove($attachment->file);
        }
    }
}
