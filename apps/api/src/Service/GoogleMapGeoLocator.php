<?php

namespace App\Service;

use App\Service\GeoLocator\GeoPointResponse;

class GoogleMapGeoLocator implements GeoLocator
{
    public function __construct(private readonly string $apiUrl, private readonly string $apiKey)
    {
    }

    public function find(string $address): GeoPointResponse
    {
        $json = @file_get_contents(sprintf('%s?key=%s&address=%s', $this->apiUrl, $this->apiKey, urlencode($address)));
        $response = false === $json ? null : json_decode($json);

        if ('OK' !== ($response->status ?? null)) {
            return $this->buildUnSucceededResponse($address);
        }

        $formattedAddress = $response->results[0]->formatted_address;
        $location = $response->results[0]->geometry->location;

        return new GeoPointResponse($formattedAddress, $location->lng, $location->lat);
    }

    private function buildUnSucceededResponse(string $address): GeoPointResponse
    {
        return new GeoPointResponse($address, 0, 0, false);
    }
}
