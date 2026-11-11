<?php

declare(strict_types=1);

namespace Prospecter\Core\Prospecting\Application\DiscardRemovedLogo;

use Prospecter\Core\Prospecting\Domain\Event\LogoRemoved;
use Prospecter\Core\SharedKernel\Application\EventHandler;
use Prospecter\Core\SharedKernel\Application\Port\Storage;

/**
 * The file of a logo leaves the storage once its removal is written, never before: a gesture that fails keeps its
 * logo whole. Delivered again, it removes nothing more.
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
