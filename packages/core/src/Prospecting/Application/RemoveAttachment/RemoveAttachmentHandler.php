<?php

declare(strict_types=1);

namespace Prospecter\Core\Prospecting\Application\RemoveAttachment;

use Prospecter\Core\Prospecting\Domain\AttachmentId;
use Prospecter\Core\Prospecting\Domain\ExchangeId;
use Prospecter\Core\Prospecting\Domain\ExchangeRepository;
use Prospecter\Core\Prospecting\Domain\ProspectorService;
use Prospecter\Core\SharedKernel\Application\CommandHandler;
use Prospecter\Core\SharedKernel\Application\Port\Events;
use Prospecter\Core\SharedKernel\Application\Port\PrivateStorage;
use Prospecter\Core\SharedKernel\Application\PublishesEvents;
use Prospecter\Core\SharedKernel\Domain\MemberId;

final readonly class RemoveAttachmentHandler implements CommandHandler
{
    use PublishesEvents;

    public function __construct(
        private ExchangeRepository $exchanges,
        private ProspectorService $prospectorService,
        private PrivateStorage $privateStorage,
        private Events $events,
    ) {
    }

    public function __invoke(RemoveAttachment $command): void
    {
        $prospector = $this->prospectorService->prospectorFrom(new MemberId($command->prospectorId));
        $exchange = $this->exchanges->get(new ExchangeId($command->exchangeId));
        $id = new AttachmentId($command->id);
        $file = $exchange->attachmentReadBy($prospector, $id)->file;
        $exchange->removeAttachment($id, by: $prospector, at: $command->executedAt);
        $this->exchanges->save($exchange);
        $this->privateStorage->remove($file);
        $this->publishWhatHappenedTo($exchange);
    }
}
