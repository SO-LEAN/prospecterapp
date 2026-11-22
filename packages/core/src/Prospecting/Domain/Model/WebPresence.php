<?php

declare(strict_types=1);

namespace Prospecter\Core\Prospecting\Domain\Model;

use Prospecter\Core\SharedKernel\Domain\ExternalReferences;

/**
 * Where an organization is found on the web: its links (its LinkedIn page...) and its website, when known.
 */
final readonly class WebPresence
{
    public function __construct(
        public ExternalReferences $references,
        public ?Website $website = null,
    ) {
    }
}
