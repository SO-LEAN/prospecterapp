<?php

namespace App\Presenter;

use Solean\CleanProspecter\UseCase\GetOrganization\GetOrganizationResponse;
use Solean\CleanProspecter\UseCase\Presenter;

class GetOrganizationPresenter implements Presenter
{
    /**
     * @param GetOrganizationResponse $response
     *
     * @return mixed
     */
    public function present($response)
    {
        $table = $this->presentTable($response);

        return [
            'page_title' => sprintf('Organization : %s', $response->getCorporateName()?: $response->getEmail()),
            'id' => $response->getId(),
            'table' => $table,
        ];

    }

    /**
     * @param GetOrganizationResponse $response
     * @return array
     */
    private function presentTable($response): array
    {
        $table = [
            ['Owned by' => $response->getOwnedBy()],
            ['Corporate name' => $response->getCorporateName()],
            ['Legal form' => $response->getForm()],
            ['Language' => $response->getLanguage()],
            ['Email' => $response->getEmail()],
            ['city' => $response->getCity()]
        ];

        if ($response->getHoldBy()) {
            $table['Holding'] = $response->getHoldBy();
        }

        if ($response->getStreet()) {
            $table['Street'] = $response->getStreet();
        }

        if ($response->getCity()) {
            $table['City'] = $response->getCity();
        }

        if ($response->getPostalCode()) {
            $table['Postal code'] = $response->getPostalCode();
        }

        if ($response->getCountry()) {
            $table['Country'] = $response->getCountry();
        }

        return $table;
    }
}
