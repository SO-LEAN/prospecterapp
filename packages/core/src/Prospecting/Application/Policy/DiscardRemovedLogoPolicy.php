<?php

declare(strict_types=1);

namespace Prospecter\Core\Prospecting\Application\Policy;

use Prospecter\Core\Prospecting\Application\Organization\DiscardRemovedLogo\DiscardRemovedLogo;
use Prospecter\Core\Prospecting\Domain\Event\LogoRemoved;
use Prospecter\Core\SharedKernel\Application\Handler\Policy;

/**
 * When an organization's logo is removed, its file leaves the storage, once the removal is written (ADR 0101).
 */
final readonly class DiscardRemovedLogoPolicy implements Policy
{
    public function __invoke(LogoRemoved $event): DiscardRemovedLogo
    {
        return new DiscardRemovedLogo($event->logo, $event->occurredAt);
    }
}
