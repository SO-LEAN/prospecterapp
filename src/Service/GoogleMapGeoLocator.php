<?php

namespace App\Service;

use App\Service\GeoLocator\GeoPointResponse;
use Exception;

class GoogleMapGeoLocator implements GeoLocator
{
    /**
     * @var string
     */
    private $apiUrl;
    /**
     * @var string
     */
    private $apiKey;

    /**
     * GeoLocationGoogleMapAdapter constructor.
     *
     * @param $apiUrl
     * @param $apiKey
     */
    public function __construct(string $apiUrl, string $apiKey)
    {
        $this->apiUrl = $apiUrl;
        $this->apiKey = $apiKey;
    }

    public function find(string $address): GeoPointResponse
    {
        try {
            $response = json_decode(file_get_contents(sprintf('%s?key=%s&address=%s', $this->apiUrl, $this->apiKey, urlencode($address))));
        } catch (Exception $e) {
            return $this->buildUnSucceededResponse($address);
        }

        if ('OK' !== $response->status) {
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
