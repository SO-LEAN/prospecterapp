<?php

declare(strict_types=1);

namespace App\Prospecting\Presenter;

use App\Prospecting\Resource\MeetingPlaceResource;
use App\Shared\Resource\AddressResource;
use Prospecter\Core\Prospecting\Application\Exchange\ProposeMeetingPlace\MeetingPlace;
use Prospecter\Core\Prospecting\Application\Exchange\ProposeMeetingPlace\ProposeMeetingPlaceOutput;

/**
 * Presents the place proposed to meet a contact. It keeps it for the call that asked for it (ADR 0085).
 */
final class MeetingPlacePresenter implements ProposeMeetingPlaceOutput
{
    public private(set) MeetingPlaceResource $resource;

    public function present(?MeetingPlace $place): void
    {
        $this->resource = new MeetingPlaceResource(null === $place ? null : new AddressResource($place->street, $place->postalCode, $place->city, $place->country));
    }
}
