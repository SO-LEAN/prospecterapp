<?php

namespace App\Prospecting\Presenter;

use App\Prospecting\Resource\EstablishmentResource;
use App\Prospecting\Resource\EstablishmentsResource;
use Prospecter\Core\Prospecting\Application\SearchEstablishments\FoundEstablishment;
use Prospecter\Core\Prospecting\Application\SearchEstablishments\SearchEstablishmentsOutput;

/**
 * The establishments proposed for an organization, kept for the call that asked them (ADR 0085).
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
