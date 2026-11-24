<?php

declare(strict_types=1);

namespace Prospecter\Core\Prospecting\Application\DiscardAttachmentsOfErasedExchange;

use Prospecter\Core\Prospecting\Application\Port\PrivateStorage;
use Prospecter\Core\SharedKernel\Application\Handler\CommandHandler;

/**
 * Removes an erased exchange's files, since they may hold personal data (ADR 0077). It removes them once the
 * erasure is written, never before. When the command comes again, it removes nothing more.
 */
final readonly class DiscardAttachmentsOfErasedExchangeHandler implements CommandHandler
{
    public function __construct(private PrivateStorage $privateStorage)
    {
    }

    public function __invoke(DiscardAttachmentsOfErasedExchange $command): void
    {
        foreach ($command->files as $file) {
            $this->privateStorage->remove($file);
        }
    }
}
