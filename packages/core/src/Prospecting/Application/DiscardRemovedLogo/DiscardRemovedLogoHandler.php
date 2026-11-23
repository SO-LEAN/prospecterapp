<?php

declare(strict_types=1);

namespace Prospecter\Core\Prospecting\Application\DiscardRemovedLogo;

use Prospecter\Core\Prospecting\Domain\Event\LogoRemoved;
use Prospecter\Core\SharedKernel\Application\Handler\EventHandler;
use Prospecter\Core\SharedKernel\Application\Port\Storage;

/**
 * Removes a logo's file from the storage once the removal is written, never before. So a gesture that fails keeps
 * its logo whole. When the event is delivered again, it removes nothing more.
 */
final readonly class DiscardRemovedLogoHandler implements EventHandler
{
    public function __construct(private Storage $storage)
    {
    }

    public function __invoke(LogoRemoved $event): void
    {
        $this->storage->remove($event->logo);
    }
}
