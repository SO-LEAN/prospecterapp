<?php

declare(strict_types=1);

namespace Prospecter\Core\Prospecting\Application\SendingNoted;

use Prospecter\Core\Prospecting\Domain\Model\MessageReference;
use Prospecter\Core\Prospecting\Domain\Port\CampaignRecipients;
use Prospecter\Core\Prospecting\Domain\Port\Prospectors;
use Prospecter\Core\Prospecting\Domain\Repository\ExchangeRepository;
use Prospecter\Core\SharedKernel\Application\QueryHandler;
use Prospecter\Core\SharedKernel\Domain\CampaignId;
use Prospecter\Core\SharedKernel\Domain\MemberId;
use Prospecter\Core\SharedKernel\Domain\PersonId;

/**
 * Fingerprints the sending as `NoteCampaignExchange` does, then reads which exchange the account noted under it.
 */
final readonly class SendingNotedHandler implements QueryHandler
{
    public function __construct(
        private ExchangeRepository $exchanges,
        private Prospectors $prospectors,
        private CampaignRecipients $campaignRecipients,
    ) {
    }

    public function __invoke(SendingNoted $query, SendingNotedOutput $output): void
    {
        $prospector = $this->prospectors->prospectorFrom(new MemberId($query->prospectorId));
        $recipient = $this->campaignRecipients->recipientIn(new CampaignId($query->campaignId), new PersonId($query->personId), $prospector->accountId);

        if (null === $recipient) {
            $output->present(null);

            return;
        }

        $reference = MessageReference::of($recipient->channel, $query->messageId, $query->linkedInConversation);
        $noted = $this->exchanges->fingerprinted($prospector->accountId, $recipient->fingerprintOfSending($query->occurredAt, $query->text, $reference));

        $output->present($noted?->exchange->value);
    }
}
