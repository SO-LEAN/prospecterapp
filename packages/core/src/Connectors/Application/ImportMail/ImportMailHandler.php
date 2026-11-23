<?php

declare(strict_types=1);

namespace Prospecter\Core\Connectors\Application\ImportMail;

use Prospecter\Core\Connectors\Contract\Event\EmailExchanged;
use Prospecter\Core\Connectors\Domain\Event\MailboxMessageAdded;
use Prospecter\Core\Connectors\Domain\Model\Connection;
use Prospecter\Core\Connectors\Domain\Model\Correspondent;
use Prospecter\Core\Connectors\Domain\Model\MailboxAttachment;
use Prospecter\Core\Connectors\Domain\Model\MailboxMail;
use Prospecter\Core\Connectors\Domain\Model\Provider;
use Prospecter\Core\Connectors\Domain\Port\Correspondents;
use Prospecter\Core\Connectors\Domain\Port\ProviderMailbox;
use Prospecter\Core\Connectors\Domain\Repository\ConnectionRepository;
use Prospecter\Core\Connectors\Domain\Repository\ProviderClientRepository;
use Prospecter\Core\SharedKernel\Application\Handler\EventHandler;
use Prospecter\Core\SharedKernel\Application\Port\Events;
use Prospecter\Core\SharedKernel\Domain\Contact\Email;

/**
 * A message was added to a member's mailbox. It may be received from a contact or from the address of an
 * organization of their account. Or the member may have sent it to them in « To ». Then the other contexts are told
 * of it once per correspondent, with its subject and its text (ADR 0154). This reaction publishes it, and no domain
 * event carries the text. Any other message imports nothing, and so does an unknown correspondent.
 */
final readonly class ImportMailHandler implements EventHandler
{
    private const string RECEIVED = 'inbound';
    private const string SENT = 'outbound';

    public function __construct(
        private ConnectionRepository $connections,
        private ProviderClientRepository $clients,
        private ProviderMailbox $mailbox,
        private Correspondents $correspondents,
        private Events $events,
    ) {
    }

    public function __invoke(MailboxMessageAdded $added): void
    {
        $connection = $this->connections->find($added->subject(), Provider::Google);
        $client = $this->clients->find($added->accountId, Provider::Google);
        $mail = null === $connection || null === $client ? null : $connection->mailOf($added->messageId, $client->credentials(), $this->mailbox);
        $correspondents = null === $mail ? [] : $this->correspondentsOf($mail, $added);
        if (null === $connection || null === $client || null === $mail || [] === $correspondents) {
            return;
        }

        $uploads = $connection->depositAttachmentsOf($mail, count($correspondents), $client->credentials(), $this->mailbox);
        foreach ($correspondents as $rank => $correspondent) {
            $this->events->publish($this->exchanged($added, $connection, $mail, $correspondent, array_column($uploads, $rank)));
        }
    }

    /**
     * @return list<Correspondent>
     */
    private function correspondentsOf(MailboxMail $mail, MailboxMessageAdded $added): array
    {
        $correspondents = array_map(fn (Email $address): ?Correspondent => Correspondent::havingTheAddress($address, $added->accountId, $this->correspondents), $mail->correspondentAddresses());

        return array_values(array_filter($correspondents));
    }

    /**
     * @param list<string> $uploads the uploads of the message's attachments for this correspondent
     */
    private function exchanged(MailboxMessageAdded $added, Connection $connection, MailboxMail $mail, Correspondent $correspondent, array $uploads): EmailExchanged
    {
        return new EmailExchanged(
            $added->accountId->value,
            $added->by->value,
            Provider::Google->value,
            $mail->identityIn($connection->mailbox()),
            $mail->isSent() ? self::SENT : self::RECEIVED,
            $correspondent->contact?->value,
            $correspondent->organization?->value,
            $mail->subject,
            $mail->text,
            $mail->exchangedAt,
            $added->occurredAt,
            $mail->providerId,
            array_map(static fn (MailboxAttachment $attachment, string $upload): array => ['upload' => $upload, 'name' => $attachment->name, 'size' => $attachment->size], $mail->attachmentsToImport(), $uploads),
            array_map(static fn (MailboxAttachment $attachment): array => ['name' => $attachment->name, 'size' => $attachment->size], $mail->attachmentsLeftOut()),
        );
    }
}
