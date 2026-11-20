<?php

declare(strict_types=1);

namespace Prospecter\Core\Connectors\Application\ImportMail;

use Prospecter\Core\Connectors\Contract\Event\EmailExchanged;
use Prospecter\Core\Connectors\Domain\Event\MailboxMessageAdded;
use Prospecter\Core\Connectors\Domain\Model\Connection;
use Prospecter\Core\Connectors\Domain\Model\Correspondent;
use Prospecter\Core\Connectors\Domain\Model\MailboxMail;
use Prospecter\Core\Connectors\Domain\Model\Provider;
use Prospecter\Core\Connectors\Domain\Port\Correspondents;
use Prospecter\Core\Connectors\Domain\Port\ProviderMailbox;
use Prospecter\Core\Connectors\Domain\Repository\ConnectionRepository;
use Prospecter\Core\Connectors\Domain\Repository\ProviderClientRepository;
use Prospecter\Core\SharedKernel\Application\EventHandler;
use Prospecter\Core\SharedKernel\Application\Port\Events;

/**
 * A message added to the mailbox of a member, received from a contact or from the address of an organization of their
 * account, or sent to them in « To », is told to the other contexts, once per correspondent, its subject and its text
 * with it (ADR 0154): published by this reaction, no domain event carrying the text. Anything else, a correspondent
 * unknown included, is nothing to import.
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
        if (null === $connection || null === $mail) {
            return;
        }

        foreach ($mail->correspondentAddresses() as $address) {
            $correspondent = Correspondent::havingTheAddress($address, $added->accountId, $this->correspondents);
            if (null !== $correspondent) {
                $this->events->publish($this->exchanged($added, $connection, $mail, $correspondent));
            }
        }
    }

    private function exchanged(MailboxMessageAdded $added, Connection $connection, MailboxMail $mail, Correspondent $correspondent): EmailExchanged
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
        );
    }
}
