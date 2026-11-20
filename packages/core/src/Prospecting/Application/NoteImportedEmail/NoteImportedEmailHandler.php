<?php

declare(strict_types=1);

namespace Prospecter\Core\Prospecting\Application\NoteImportedEmail;

use Prospecter\Core\Connectors\Contract\Event\EmailExchanged;
use Prospecter\Core\Prospecting\Domain\Exception\ContactToAttachOfAnother;
use Prospecter\Core\Prospecting\Domain\Exception\ExchangeAlreadyNoted;
use Prospecter\Core\Prospecting\Domain\Exception\ExchangeErasedBefore;
use Prospecter\Core\Prospecting\Domain\Exception\UnknownContact;
use Prospecter\Core\Prospecting\Domain\Exception\UnknownOrganization;
use Prospecter\Core\Prospecting\Domain\Model\Channel;
use Prospecter\Core\Prospecting\Domain\Model\Direction;
use Prospecter\Core\Prospecting\Domain\Model\Exchange;
use Prospecter\Core\Prospecting\Domain\Model\ExchangeFacts;
use Prospecter\Core\Prospecting\Domain\Model\ExchangeId;
use Prospecter\Core\Prospecting\Domain\Model\ImportedEmail;
use Prospecter\Core\Prospecting\Domain\Model\Prospector;
use Prospecter\Core\Prospecting\Domain\Port\Prospectors;
use Prospecter\Core\Prospecting\Domain\Repository\ExchangeRepository;
use Prospecter\Core\Prospecting\Domain\Repository\OrganizationRepository;
use Prospecter\Core\Prospecting\Domain\Service\ContactOrganizations;
use Prospecter\Core\Prospecting\Domain\Service\NotedOnce;
use Prospecter\Core\SharedKernel\Application\EventHandler;
use Prospecter\Core\SharedKernel\Application\Port\Events;
use Prospecter\Core\SharedKernel\Application\PublishesEvents;
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
        private Events $events,
    ) {
    }

    public function __invoke(EmailExchanged $email): void
    {
        $prospector = $this->prospectors->prospectorFrom(new MemberId($email->memberId));
        $correspondent = null === $email->contactId ? new OrganizationId((string) $email->organizationId) : new PersonId($email->contactId);
        $id = ExchangeId::ofImportedEmail($prospector->accountId, $email->emailId, $correspondent);
        $exchange = $this->exchangeWith($correspondent, $id, $prospector, $email);
        if (null === $exchange || $this->wasNotedBefore($exchange)) {
            return;
        }

        $this->exchanges->save($exchange);
        $this->publishWhatHappenedTo($exchange);
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
    private function exchangeWith(PersonId|OrganizationId $correspondent, ExchangeId $id, Prospector $prospector, EmailExchanged $email): ?Exchange
    {
        try {
            return $correspondent instanceof PersonId
                ? $this->withAContact($id, $correspondent, $prospector, $email)
                : $this->withTheOrganization($id, $correspondent, $prospector, $email);
        } catch (UnknownContact|ContactToAttachOfAnother|UnknownOrganization) {
            return null;
        }
    }

    private function withAContact(ExchangeId $id, PersonId $contact, Prospector $prospector, EmailExchanged $email): Exchange
    {
        return Exchange::importedWithAContact(
            $prospector->accountId,
            $prospector->id,
            $id,
            $contact,
            $this->contactOrganizations->keepersOf($contact, $prospector),
            self::factsOf($email),
            self::importedFrom($email),
            $email->occurredAt,
        );
    }

    private function withTheOrganization(ExchangeId $id, OrganizationId $organization, Prospector $prospector, EmailExchanged $email): Exchange
    {
        return Exchange::importedWithTheOrganization(
            $this->organizations->get($organization),
            $prospector->id,
            $id,
            self::factsOf($email),
            self::importedFrom($email),
            $email->occurredAt,
        );
    }

    /**
     * Its text is its subject followed by its text, as a member would note it.
     */
    private static function factsOf(EmailExchanged $email): ExchangeFacts
    {
        return new ExchangeFacts(Channel::Email, Direction::from($email->direction), $email->exchangedAt, sprintf("%s\n\n%s", $email->subject, $email->text));
    }

    private static function importedFrom(EmailExchanged $email): ImportedEmail
    {
        return new ImportedEmail($email->provider, $email->emailId);
    }
}
