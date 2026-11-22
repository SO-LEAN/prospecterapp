<?php

declare(strict_types=1);

namespace Prospecter\Core\Connectors\Application\AskForAuthorization;

use Prospecter\Core\Connectors\Domain\Exception\ProviderUnavailable;
use Prospecter\Core\Connectors\Domain\Model\AuthorizationRequest;
use Prospecter\Core\Connectors\Domain\Model\PendingAuthorization;
use Prospecter\Core\Connectors\Domain\Model\Provider;
use Prospecter\Core\Connectors\Domain\Model\Scope;
use Prospecter\Core\Connectors\Domain\Port\Members;
use Prospecter\Core\Connectors\Domain\Repository\AuthorizationRepository;
use Prospecter\Core\Connectors\Domain\Repository\ProviderClientRepository;
use Prospecter\Core\SharedKernel\Application\CommandHandler;
use Prospecter\Core\SharedKernel\Domain\MemberId;

final readonly class AskForAuthorizationHandler implements CommandHandler
{
    public function __construct(
        private AuthorizationRepository $authorizations,
        private ProviderClientRepository $clients,
        private Members $members,
    ) {
    }

    public function __invoke(AskForAuthorization $command): void
    {
        $me = new MemberId($command->memberId);
        $provider = Provider::from($command->provider);
        if (null === $this->clients->find($this->members->get($me)->accountId, $provider)) {
            throw ProviderUnavailable::withoutClient($provider);
        }

        $this->authorizations->keep(PendingAuthorization::askedBy($me, new AuthorizationRequest($provider, $command->state, $command->codeVerifier, [Scope::Calendar]), $command->executedAt));
    }
}
