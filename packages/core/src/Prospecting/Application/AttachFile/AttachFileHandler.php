<?php

declare(strict_types=1);

namespace Prospecter\Core\Prospecting\Application\AttachFile;

use Prospecter\Core\Prospecting\Domain\Attachment;
use Prospecter\Core\Prospecting\Domain\AttachmentId;
use Prospecter\Core\Prospecting\Domain\ExchangeId;
use Prospecter\Core\Prospecting\Domain\ExchangeRepository;
use Prospecter\Core\Prospecting\Domain\ProspectorService;
use Prospecter\Core\SharedKernel\Application\CommandHandler;
use Prospecter\Core\SharedKernel\Application\Port\Events;
use Prospecter\Core\SharedKernel\Application\Port\PrivateStorage;
use Prospecter\Core\SharedKernel\Application\PublishesEvents;
use Prospecter\Core\SharedKernel\Domain\MemberId;

/**
 * The upload is kept once the exchange accepted it: a refused file stays an upload, which the storage forgets.
 */
final readonly class AttachFileHandler implements CommandHandler
{
    use PublishesEvents;

    public function __construct(
        private ExchangeRepository $exchanges,
        private ProspectorService $prospectorService,
        private PrivateStorage $privateStorage,
        private Events $events,
    ) {
    }

    public function __invoke(AttachFile $command): void
    {
        $prospector = $this->prospectorService->prospectorFrom(new MemberId($command->prospectorId));
        $exchange = $this->exchanges->get(new ExchangeId($command->exchangeId));
        $upload = $this->privateStorage->uploaded($command->upload);
        $exchange->attach(new Attachment(new AttachmentId($command->id), $command->name, $upload->size, $upload->mediaType, $command->upload), by: $prospector, at: $command->executedAt);
        $this->privateStorage->keep($command->upload);
        $this->exchanges->save($exchange);
        $this->publishWhatHappenedTo($exchange);
    }
}
