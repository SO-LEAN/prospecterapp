<?php

declare(strict_types=1);

namespace Prospecter\Core\Connectors\Application\AskForAuthorization;

use Prospecter\Core\Connectors\Domain\Model\PendingAuthorization;
use Prospecter\Core\Connectors\Domain\Model\Provider;
use Prospecter\Core\Connectors\Domain\Repository\AuthorizationRepository;
use Prospecter\Core\SharedKernel\Application\CommandHandler;
use Prospecter\Core\SharedKernel\Domain\MemberId;

final readonly class AskForAuthorizationHandler implements CommandHandler
{
    public function __construct(private AuthorizationRepository $authorizations)
    {
    }

    public function __invoke(AskForAuthorization $command): void
    {
        $this->authorizations->keep(PendingAuthorization::askedBy(
            new MemberId($command->memberId),
            Provider::from($command->provider),
            $command->state,
            $command->codeVerifier,
            $command->executedAt,
        ));
    }
}
