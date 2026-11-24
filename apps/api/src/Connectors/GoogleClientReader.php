<?php

declare(strict_types=1);

namespace App\Connectors;

use App\Connectors\Google\GoogleEndpoints;
use App\Connectors\Resource\GoogleClientResource;
use App\Security\User;
use App\Shared\Links;
use Prospecter\Core\Connectors\Application\Client\ConsultMyAccountClients\ConsultMyAccountClients;
use Prospecter\Core\SharedKernel\Application\Handler\QueryHandler;

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
        $this->consultMyAccountClients->__invoke(new ConsultMyAccountClients($me->memberId()), $presenter);

        return $presenter->resource;
    }
}
