<?php

declare(strict_types=1);

namespace Prospecter\Core\Connectors\Application\AskForAuthorization;

use Prospecter\Core\Connectors\Domain\Exception\ProviderUnavailable;
use Prospecter\Core\Connectors\Domain\Model\PendingAuthorization;
use Prospecter\Core\Connectors\Domain\Model\Provider;
use Prospecter\Core\Connectors\Domain\Port\ProviderAccess;
use Prospecter\Core\Connectors\Domain\Repository\AuthorizationRepository;
use Prospecter\Core\SharedKernel\Application\CommandHandler;
use Prospecter\Core\SharedKernel\Domain\MemberId;

final readonly class AskForAuthorizationHandler implements CommandHandler
{
    public function __construct(
        private AuthorizationRepository $authorizations,
        private ProviderAccess $providerAccess,
    ) {
    }

    public function __invoke(AskForAuthorization $command): void
    {
        $provider = Provider::from($command->provider);
        if (!$this->providerAccess->hasClient($provider)) {
            throw ProviderUnavailable::withoutClient($provider);
        }

        $this->authorizations->keep(PendingAuthorization::askedBy(
            new MemberId($command->memberId),
            $provider,
            $command->state,
            $command->codeVerifier,
            $command->executedAt,
        ));
    }
}
