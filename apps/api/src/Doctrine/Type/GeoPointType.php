<?php

namespace App\Doctrine\Type;

use Prospecter\Core\Domain\Organization\GeoPoint;

/**
 * @extends ValueObjectJsonType<GeoPoint>
 */
final class GeoPointType extends ValueObjectJsonType
{
    protected function toArray(object $value): array
    {
        return ['longitude' => $value->longitude, 'latitude' => $value->latitude];
    }

    protected function fromArray(array $data): GeoPoint
    {
        return new GeoPoint((float) $data['longitude'], (float) $data['latitude']);
    }
}
