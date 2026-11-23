<?php

declare(strict_types=1);

namespace Prospecter\Core\Prospecting\Application\NoteOrganizationExchange;

use Prospecter\Core\Prospecting\Domain\Exception\ReferenceNotOfTheChannel;
use Prospecter\Core\Prospecting\Domain\Model\Channel;
use Prospecter\Core\Prospecting\Domain\Model\ContactCorrespondent;
use Prospecter\Core\Prospecting\Domain\Model\Direction;
use Prospecter\Core\Prospecting\Domain\Model\Exchange;
use Prospecter\Core\Prospecting\Domain\Model\ExchangeFacts;
use Prospecter\Core\Prospecting\Domain\Model\ExchangeId;
use Prospecter\Core\Prospecting\Domain\Model\ExchangeStatus;
use Prospecter\Core\Prospecting\Domain\Model\MessageReference;
use Prospecter\Core\Prospecting\Domain\Model\Noting;
use Prospecter\Core\Prospecting\Domain\Model\Organization;
use Prospecter\Core\Prospecting\Domain\Model\OrganizationCorrespondent;
use Prospecter\Core\Prospecting\Domain\Repository\ExchangeRepository;
use Prospecter\Core\Prospecting\Domain\Repository\OrganizationRepository;
use Prospecter\Core\Prospecting\Domain\Service\ContactOrganizations;
use Prospecter\Core\Prospecting\Domain\Service\NotedOnce;
use Prospecter\Core\SharedKernel\Application\CommandHandler;
use Prospecter\Core\SharedKernel\Application\Port\Events;
use Prospecter\Core\SharedKernel\Application\PublishesEvents;
use Prospecter\Core\SharedKernel\Domain\Gesture;
use Prospecter\Core\SharedKernel\Domain\MemberId;
use Prospecter\Core\SharedKernel\Domain\OrganizationId;
use Prospecter\Core\SharedKernel\Domain\Origin;
use Prospecter\Core\SharedKernel\Domain\PersonId;

final readonly class NoteOrganizationExchangeHandler implements CommandHandler
{
    use PublishesEvents;

    public function __construct(
        private ExchangeRepository $exchanges,
        private OrganizationRepository $organizations,
        private ContactOrganizations $contactOrganizations,
        private NotedOnce $notedOnce,
        private Events $events,
    ) {
    }

    public function __invoke(NoteOrganizationExchange $command): void
    {
        $author = new MemberId($command->prospectorId);
        $organization = $this->organizations->get(new OrganizationId($command->organizationId));
        $exchange = null === $command->personId
            ? Exchange::noted(self::gestureOf($organization, $author, $command), new ExchangeId($command->id), new OrganizationCorrespondent($organization->id()), self::notingOf($command))
            : $this->notedWithAContact($organization, $author, new PersonId($command->personId), $command);
        if ($command->noteOnce) {
            $this->notedOnce->ensureFirst($exchange);
        } else {
            $this->notedOnce->fingerprintUnlessNotedBefore($exchange);
        }
        $this->exchanges->save($exchange);
        $this->publishWhatHappenedTo($exchange);
    }

    /**
     * Like any other exchange with a contact, the exchange is kept for every organization the contact is part of.
     */
    private function notedWithAContact(Organization $organization, MemberId $author, PersonId $contact, NoteOrganizationExchange $command): Exchange
    {
        $organization->ensureHasContact($contact);

        return Exchange::noted(self::gestureOf($organization, $author, $command), new ExchangeId($command->id), new ContactCorrespondent($contact, $this->contactOrganizations->of($contact, $organization->accountId())), self::notingOf($command));
    }

    private static function gestureOf(Organization $organization, MemberId $author, NoteOrganizationExchange $command): Gesture
    {
        return new Gesture($organization->accountId(), $author, $command->executedAt, Origin::byAnAI($command->byAnAI));
    }

    /**
     * @throws ReferenceNotOfTheChannel
     */
    private static function notingOf(NoteOrganizationExchange $command): Noting
    {
        return Noting::of(self::factsOf($command), ExchangeStatus::outcome($command->missed), self::referenceOf($command));
    }

    /**
     * @throws ReferenceNotOfTheChannel
     */
    private static function referenceOf(NoteOrganizationExchange $command): MessageReference
    {
        return MessageReference::of(Channel::from($command->channel), $command->messageId, $command->linkedInConversation);
    }

    private static function factsOf(NoteOrganizationExchange $command): ExchangeFacts
    {
        return new ExchangeFacts(Channel::from($command->channel), Direction::from($command->direction), $command->occurredAt, $command->text);
    }
}
