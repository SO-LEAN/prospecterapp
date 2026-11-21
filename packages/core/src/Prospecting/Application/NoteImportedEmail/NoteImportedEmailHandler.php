<?php

declare(strict_types=1);

namespace Prospecter\Core\Prospecting\Application\NoteImportedEmail;

use Prospecter\Core\Connectors\Contract\Event\EmailExchanged;
use Prospecter\Core\Prospecting\Domain\Exception\ContactToAttachOfAnother;
use Prospecter\Core\Prospecting\Domain\Exception\ExchangeAlreadyNoted;
use Prospecter\Core\Prospecting\Domain\Exception\ExchangeErasedBefore;
use Prospecter\Core\Prospecting\Domain\Exception\UnknownContact;
use Prospecter\Core\Prospecting\Domain\Exception\UnknownOrganization;
use Prospecter\Core\Prospecting\Domain\Model\Attachment;
use Prospecter\Core\Prospecting\Domain\Model\AttachmentId;
use Prospecter\Core\Prospecting\Domain\Model\Channel;
use Prospecter\Core\Prospecting\Domain\Model\Direction;
use Prospecter\Core\Prospecting\Domain\Model\Exchange;
use Prospecter\Core\Prospecting\Domain\Model\ExchangeFacts;
use Prospecter\Core\Prospecting\Domain\Model\ExchangeId;
use Prospecter\Core\Prospecting\Domain\Model\ImportedEmail;
use Prospecter\Core\Prospecting\Domain\Model\Prospector;
use Prospecter\Core\Prospecting\Domain\Policy\AttachmentLeftOut;
use Prospecter\Core\Prospecting\Domain\Port\Prospectors;
use Prospecter\Core\Prospecting\Domain\Repository\ExchangeRepository;
use Prospecter\Core\Prospecting\Domain\Repository\OrganizationRepository;
use Prospecter\Core\Prospecting\Domain\Service\ContactOrganizations;
use Prospecter\Core\Prospecting\Domain\Service\NotedOnce;
use Prospecter\Core\SharedKernel\Application\EventHandler;
use Prospecter\Core\SharedKernel\Application\Port\Events;
use Prospecter\Core\SharedKernel\Application\Port\PrivateStorage;
use Prospecter\Core\SharedKernel\Application\PublishesEvents;
use Prospecter\Core\SharedKernel\Domain\Exception\UnknownUpload;
use Prospecter\Core\SharedKernel\Domain\MemberId;
use Prospecter\Core\SharedKernel\Domain\OrganizationId;
use Prospecter\Core\SharedKernel\Domain\PersonId;

/**
 * An e-mail the mailbox of a member exchanged with a contact, or with an organization itself, is noted as an exchange
 * by that member (ADR 0154).
 */
final readonly class NoteImportedEmailHandler implements EventHandler
{
    use PublishesEvents;

    public function __construct(
        private ExchangeRepository $exchanges,
        private OrganizationRepository $organizations,
        private Prospectors $prospectors,
        private ContactOrganizations $contactOrganizations,
        private NotedOnce $notedOnce,
        private PrivateStorage $privateStorage,
        private Events $events,
    ) {
    }

    public function __invoke(EmailExchanged $email): void
    {
        $prospector = $this->prospectors->prospectorFrom(new MemberId($email->memberId));
        $correspondent = null === $email->contactId ? new OrganizationId((string) $email->organizationId) : new PersonId($email->contactId);
        $id = ExchangeId::ofImportedEmail($prospector->accountId, $email->emailId, $correspondent);
        [$attachments, $leftOut] = $this->attachmentsOf($email, $id);
        $exchange = $this->exchangeWith($correspondent, $id, $prospector, $email, self::factsOf($email, $leftOut));
        if (null === $exchange || $this->wasNotedBefore($exchange)) {
            return;
        }

        foreach ($attachments as $attachment) {
            $exchange->importAttachment($attachment, by: $prospector->id, at: $email->occurredAt);
        }
        $this->exchanges->save($exchange);
        $this->publishWhatHappenedTo($exchange);
    }

    /**
     * Its files, sent to the storage by the import as the uploads of this exchange, and those it cannot bring: left
     * out by the import, or no longer in the storage, its uploads gone after a day (ADR 0182).
     *
     * @return array{list<Attachment>, list<array{name: string, size: int}>}
     */
    private function attachmentsOf(EmailExchanged $email, ExchangeId $exchange): array
    {
        $attachments = [];
        $leftOut = $email->attachmentsLeftOut;
        foreach ($email->attachments as $attachment) {
            try {
                $upload = $this->privateStorage->uploaded($attachment['upload']);
                $attachments[] = new Attachment(AttachmentId::ofImportedEmail($exchange, $attachment['upload']), $attachment['name'], $upload->size, $upload->mediaType, $attachment['upload']);
            } catch (UnknownUpload) {
                $leftOut[] = ['name' => $attachment['name'], 'size' => $attachment['size']];
            }
        }

        return [$attachments, $leftOut];
    }

    /**
     * Imported, noted by an AI, or by hand, erased since or not: an e-mail is noted once in its account (ADR 0166).
     */
    private function wasNotedBefore(Exchange $exchange): bool
    {
        try {
            $this->notedOnce->ensureFirst($exchange);

            return false;
        } catch (ExchangeAlreadyNoted|ExchangeErasedBefore) {
            return true;
        }
    }

    /**
     * None with a contact forgotten, or an organization no longer prospected, since the e-mail was read, nor with a
     * contact another member holds to attach, met by them only (ADR 0146).
     */
    private function exchangeWith(PersonId|OrganizationId $correspondent, ExchangeId $id, Prospector $prospector, EmailExchanged $email, ExchangeFacts $facts): ?Exchange
    {
        try {
            return $correspondent instanceof PersonId
                ? $this->withAContact($id, $correspondent, $prospector, $email, $facts)
                : $this->withTheOrganization($id, $correspondent, $prospector, $email, $facts);
        } catch (UnknownContact|ContactToAttachOfAnother|UnknownOrganization) {
            return null;
        }
    }

    private function withAContact(ExchangeId $id, PersonId $contact, Prospector $prospector, EmailExchanged $email, ExchangeFacts $facts): Exchange
    {
        return Exchange::importedWithAContact(
            $prospector->accountId,
            $prospector->id,
            $id,
            $contact,
            $this->contactOrganizations->keepersOf($contact, $prospector),
            $facts,
            self::importedFrom($email),
            $email->occurredAt,
        );
    }

    private function withTheOrganization(ExchangeId $id, OrganizationId $organization, Prospector $prospector, EmailExchanged $email, ExchangeFacts $facts): Exchange
    {
        return Exchange::importedWithTheOrganization(
            $this->organizations->get($organization),
            $prospector->id,
            $id,
            $facts,
            self::importedFrom($email),
            $email->occurredAt,
        );
    }

    /**
     * Its text is its subject followed by its text, as a member would note it, then a line for each file it could not
     * bring (ADR 0182).
     *
     * @param list<array{name: string, size: int}> $leftOut
     */
    private static function factsOf(EmailExchanged $email, array $leftOut): ExchangeFacts
    {
        $mentions = array_map(static fn (array $attachment): string => "\n".AttachmentLeftOut::mention($attachment['name'], $attachment['size']), $leftOut);

        return new ExchangeFacts(Channel::Email, Direction::from($email->direction), $email->exchangedAt, sprintf("%s\n\n%s", $email->subject, $email->text).implode('', $mentions));
    }

    private static function importedFrom(EmailExchanged $email): ImportedEmail
    {
        return new ImportedEmail($email->provider, $email->emailId, $email->providerMessageId);
    }
}
