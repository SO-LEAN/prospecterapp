<?php

namespace App\Connectors;

use App\Connectors\Google\GoogleEndpoints;
use App\Connectors\Resource\GoogleClientResource;
use App\Security\User;
use App\Shared\Links;
use Prospecter\Core\Connectors\Application\ConsultMyAccountClients\ConsultMyAccountClients;
use Prospecter\Core\SharedKernel\Application\QueryHandler;

final readonly class GoogleClientReader
{
    public function __construct(
        private QueryHandler $consultMyAccountClients,
        private Links $links,
        private GoogleEndpoints $googleEndpoints,
        private GmailNotificationEndpoint $gmailNotificationEndpoint,
    ) {
    }

    public function read(User $me): GoogleClientResource
    {
        $presenter = new GoogleClientResourcePresenter($this->links, $this->googleEndpoints->redirectUri, $this->gmailNotificationEndpoint->of($me->accountId()));
        ($this->consultMyAccountClients)(new ConsultMyAccountClients($me->memberId()), $presenter);

        return $presenter->resource;
    }
}
