<?php

declare(strict_types=1);

namespace Prospecter\Core\Entity;

interface GeoLocatable
{
    public function getAddress(): ?Address;

    public function setGeoPoint(?GeoPoint $geoPoint): void;
}
