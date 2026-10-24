<?php

declare(strict_types=1);

namespace Prospecter\Core\Prospecting\Application\RemoveAttachment;

use Prospecter\Core\Prospecting\Domain\AttachmentId;
use Prospecter\Core\Prospecting\Domain\ExchangeId;
use Prospecter\Core\Prospecting\Domain\ExchangeRepository;
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
        private PrivateStorage $privateStorage,
        private Events $events,
    ) {
    }

    public function __invoke(RemoveAttachment $command): void
    {
        $exchange = $this->exchanges->get(new ExchangeId($command->exchangeId));
        $removed = $exchange->removeAttachment(new AttachmentId($command->id), by: new MemberId($command->prospectorId), at: $command->executedAt);
        $this->exchanges->save($exchange);
        $this->privateStorage->remove($removed->file);
        $this->publishWhatHappenedTo($exchange);
    }
}
