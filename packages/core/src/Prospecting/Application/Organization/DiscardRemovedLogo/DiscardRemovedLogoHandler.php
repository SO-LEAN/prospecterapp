<?php

declare(strict_types=1);

namespace Prospecter\Core\Prospecting\Application\Organization\DiscardRemovedLogo;

use Prospecter\Core\SharedKernel\Application\Handler\CommandHandler;
use Prospecter\Core\SharedKernel\Application\Port\Storage;

/**
 * Removes a logo's file from the storage once the removal is written, never before. So a gesture that fails keeps
 * its logo whole. When the command comes again, it removes nothing more.
 */
final readonly class DiscardRemovedLogoHandler implements CommandHandler
{
    public function __construct(private Storage $storage)
    {
    }

    public function __invoke(DiscardRemovedLogo $command): void
    {
        $this->storage->remove($command->logo);
    }
}
