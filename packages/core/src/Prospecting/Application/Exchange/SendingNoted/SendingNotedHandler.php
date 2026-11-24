<?php

declare(strict_types=1);

namespace Prospecter\Core\Prospecting\Application\Exchange\SendingNoted;

use Prospecter\Core\Prospecting\Domain\Model\CampaignId;
use Prospecter\Core\Prospecting\Domain\Model\ContactId;
use Prospecter\Core\Prospecting\Domain\Model\MessageReference;
use Prospecter\Core\Prospecting\Domain\Port\CampaignRecipients;
use Prospecter\Core\Prospecting\Domain\Port\Prospectors;
use Prospecter\Core\Prospecting\Domain\Repository\ExchangeRepository;
use Prospecter\Core\SharedKernel\Application\Handler\QueryHandler;
use Prospecter\Core\SharedKernel\Domain\Identity\MemberId;

/**
 * Fingerprints the message as `NoteCampaignExchange` does. Then it reads which exchange the account logged under that
 * fingerprint.
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
        $recipient = $this->campaignRecipients->recipientIn(new CampaignId($query->campaignId), new ContactId($query->personId), $prospector->accountId);

        if (null === $recipient) {
            $output->present(null);

            return;
        }

        $reference = MessageReference::of($recipient->channel, $query->messageId, $query->linkedInConversation);
        $noted = $this->exchanges->fingerprinted($prospector->accountId, $recipient->fingerprintOfSending($query->occurredAt, $query->text, $reference));

        $output->present($noted?->exchange->value);
    }
}
