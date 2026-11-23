<?php

declare(strict_types=1);

namespace App\Prospecting\Resource;

/**
 * The establishments proposed for an organization. Those in its municipality come first.
 */
final readonly class EstablishmentsResource
{
    /**
     * @param list<EstablishmentResource> $establishments
     */
    public function __construct(
        public array $establishments,
    ) {
    }
}
