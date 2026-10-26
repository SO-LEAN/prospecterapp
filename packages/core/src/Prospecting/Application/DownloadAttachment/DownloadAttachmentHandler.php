<?php

declare(strict_types=1);

namespace Prospecter\Core\Prospecting\Application\DownloadAttachment;

use Prospecter\Core\Prospecting\Domain\AttachmentId;
use Prospecter\Core\Prospecting\Domain\ExchangeId;
use Prospecter\Core\Prospecting\Domain\ExchangeRepository;
use Prospecter\Core\Prospecting\Domain\ProspectorService;
use Prospecter\Core\SharedKernel\Application\Port\PrivateStorage;
use Prospecter\Core\SharedKernel\Application\QueryHandler;
use Prospecter\Core\SharedKernel\Domain\MemberId;

final readonly class DownloadAttachmentHandler implements QueryHandler
{
    public function __construct(
        private ExchangeRepository $exchanges,
        private ProspectorService $prospectorService,
        private PrivateStorage $privateStorage,
    ) {
    }

    public function __invoke(DownloadAttachment $query, DownloadAttachmentOutput $output): void
    {
        $prospector = $this->prospectorService->prospectorFrom(new MemberId($query->prospectorId));
        $attachment = $this->exchanges->get(new ExchangeId($query->exchangeId))->attachmentReadBy($prospector, new AttachmentId($query->id));

        $output->present(new AttachmentDownloadResponse($attachment->name, $this->privateStorage->downloadLinkOf($attachment->file, $attachment->name)));
    }
}
