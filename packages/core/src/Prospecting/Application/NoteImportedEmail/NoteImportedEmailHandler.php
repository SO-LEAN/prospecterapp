<?php

declare(strict_types=1);

namespace Prospecter\Core\Prospecting\Application\NoteImportedEmail;

use Prospecter\Core\Connectors\Contract\Event\EmailExchanged;
use Prospecter\Core\Prospecting\Domain\Exception\AttachmentTooHeavy;
use Prospecter\Core\Prospecting\Domain\Exception\ContactToAttachOfAnother;
use Prospecter\Core\Prospecting\Domain\Exception\ExchangeAlreadyNoted;
use Prospecter\Core\Prospecting\Domain\Exception\ExchangeErasedBefore;
use Prospecter\Core\Prospecting\Domain\Exception\UnknownContact;
use Prospecter\Core\Prospecting\Domain\Exception\UnknownOrganization;
use Prospecter\Core\Prospecting\Domain\Model\Attachment;
use Prospecter\Core\Prospecting\Domain\Model\AttachmentId;
use Prospecter\Core\Prospecting\Domain\Model\Channel;
use Prospecter\Core\Prospecting\Domain\Model\ContactCorrespondent;
use Prospecter\Core\Prospecting\Domain\Model\Direction;
use Prospecter\Core\Prospecting\Domain\Model\Exchange;
use Prospecter\Core\Prospecting\Domain\Model\ExchangeFacts;
use Prospecter\Core\Prospecting\Domain\Model\ExchangeId;
use Prospecter\Core\Prospecting\Domain\Model\ImportedEmail;
use Prospecter\Core\Prospecting\Domain\Model\Noting;
use Prospecter\Core\Prospecting\Domain\Model\OrganizationCorrespondent;
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
use Prospecter\Core\SharedKernel\Domain\Gesture;
use Prospecter\Core\SharedKernel\Domain\MemberId;
use Prospecter\Core\SharedKernel\Domain\OrganizationId;
use Prospecter\Core\SharedKernel\Domain\PersonId;

/**
 * A member's mailbox exchanged an e-mail with a contact, or with an organization itself. The e-mail is logged as an
 * exchange by that member (ADR 0154).
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
     * Returns the e-mail's files, and those it cannot bring. The import sent its files to the storage as this
     * exchange's uploads. A file cannot come when the import left it out, or when it is no longer in the storage
     * because its upload expired after a day. It cannot come either when the exchange has no room left for it
     * (ADR 0077, 0182).
     *
     * @return array{list<Attachment>, list<array{name: string, size: int}>}
     */
    private function attachmentsOf(EmailExchanged $email, ExchangeId $exchange): array
    {
        $attachments = [];
        $leftOut = $email->attachmentsLeftOut;
        foreach ($email->attachments as $attachment) {
            $joined = count($attachments) < Exchange::MOST_ATTACHMENTS ? $this->joined($attachment, $exchange) : null;
            if (null === $joined) {
                $leftOut[] = ['name' => $attachment['name'], 'size' => $attachment['size']];
                continue;
            }
            $attachments[] = $joined;
        }

        return [$attachments, $leftOut];
    }

    /**
     * Returns none once its upload is gone, or when the file is heavier than an exchange holds.
     *
     * @param array{upload: string, name: string, size: int} $attachment
     */
    private function joined(array $attachment, ExchangeId $exchange): ?Attachment
    {
        try {
            $upload = $this->privateStorage->uploaded($attachment['upload']);

            return new Attachment(AttachmentId::ofImportedEmail($exchange, $attachment['upload']), $attachment['name'], $upload->size, $upload->mediaType, $attachment['upload']);
        } catch (UnknownUpload|AttachmentTooHeavy) {
            return null;
        }
    }

    /**
     * An e-mail is logged once in its account (ADR 0166). This holds whether it was imported, logged by an AI or by
     * hand, and whether it was erased since or not.
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
     * Returns none when the contact was forgotten, or the organization is no longer prospected, since the e-mail was
     * read. It also returns none for a contact to attach that another member holds, because only that member met them
     * (ADR 0146).
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
        return Exchange::noted(
            new Gesture($prospector->accountId, $prospector->id, $email->occurredAt),
            $id,
            new ContactCorrespondent($contact, $this->contactOrganizations->keepersOf($contact, $prospector)),
            Noting::imported($facts, self::importedFrom($email)),
        );
    }

    private function withTheOrganization(ExchangeId $id, OrganizationId $organizationId, Prospector $prospector, EmailExchanged $email, ExchangeFacts $facts): Exchange
    {
        $organization = $this->organizations->get($organizationId);

        return Exchange::noted(
            new Gesture($organization->accountId(), $prospector->id, $email->occurredAt),
            $id,
            new OrganizationCorrespondent($organization->id()),
            Noting::imported($facts, self::importedFrom($email)),
        );
    }

    /**
     * The exchange's text is the e-mail's subject followed by its text, as a member would log it. A line follows for
     * each file the e-mail could not bring (ADR 0182).
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
