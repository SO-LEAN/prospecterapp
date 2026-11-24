<?php

declare(strict_types=1);

namespace App\Prospecting\Presenter;

use App\Prospecting\Resource\EstablishmentResource;
use App\Prospecting\Resource\EstablishmentsResource;
use Prospecter\Core\Prospecting\Application\Organization\SearchEstablishments\FoundEstablishment;
use Prospecter\Core\Prospecting\Application\Organization\SearchEstablishments\SearchEstablishmentsOutput;

/**
 * Presents the establishments proposed for an organization. It keeps them for the call that asked for them (ADR
 * 0085).
 */
final class EstablishmentsPresenter implements SearchEstablishmentsOutput
{
    public private(set) EstablishmentsResource $resource;

    /**
     * @param list<FoundEstablishment> $establishments
     */
    public function present(array $establishments): void
    {
        $this->resource = new EstablishmentsResource(array_map(static fn (FoundEstablishment $establishment): EstablishmentResource => new EstablishmentResource(
            $establishment->siret,
            $establishment->name,
            $establishment->address,
            $establishment->activity,
            $establishment->closed,
        ), $establishments));
    }
}
