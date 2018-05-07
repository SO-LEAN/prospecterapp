<?php

namespace App\Service;

use Exception;
use App\Service\GeoLocator\GeoPointResponse;

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
     * @param $apiUrl
     * @param $apiKey
     */
    public function __construct(string $apiUrl, string $apiKey)
    {
        $this->apiUrl = $apiUrl;
        $this->apiKey = $apiKey;
    }

    /**
     * @param string $address
     * @return GeoPointResponse
     */
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

    /**
     * @param string $address
     * @return GeoPointResponse
     */
    private function buildUnSucceededResponse(string $address): GeoPointResponse
    {
        return new GeoPointResponse($address, 0, 0, false);
    }
}
