<?php

declare(strict_types=1);

namespace Tests\Core\Double;

use Prospecter\Core\Prospecting\Application\ConsultExchange\AttachmentDetail;
use Prospecter\Core\Prospecting\Application\ConsultExchange\ExchangeDetail;
use Prospecter\Core\Prospecting\Application\Port\ExchangeDetailRetriever;
use Prospecter\Core\Prospecting\Domain\Exception\UnknownExchange;
use Prospecter\Core\Prospecting\Domain\Model\Attachment;
use Prospecter\Core\Prospecting\Domain\Model\Cancellation;
use Prospecter\Core\Prospecting\Domain\Model\ContactPresence;
use Prospecter\Core\Prospecting\Domain\Model\Correction;
use Prospecter\Core\Prospecting\Domain\Model\Exchange;
use Prospecter\Core\Prospecting\Domain\Model\ExchangeFacts;
use Prospecter\Core\Prospecting\Domain\Model\ExchangeId;
use Prospecter\Core\Prospecting\Domain\Model\ExchangeIntent;
use Prospecter\Core\Prospecting\Domain\Model\ExchangeSituation;
use Prospecter\Core\Prospecting\Domain\Model\ExchangeStatus;
use Prospecter\Core\Prospecting\Domain\Model\ImportedEmail;
use Prospecter\Core\Prospecting\Domain\Model\Prospector;
use Prospecter\Core\Prospecting\Domain\Policy\ExchangeConformity;
use Prospecter\Core\Prospecting\Domain\Policy\ExchangePossibilities;
use Prospecter\Core\Prospecting\Domain\Policy\ExchangeReading;
use Prospecter\Core\SharedKernel\Domain\CampaignId;
use Prospecter\Core\SharedKernel\Domain\MemberId;
use Prospecter\Core\SharedKernel\Domain\OrganizationId;
use Prospecter\Core\SharedKernel\Domain\Origin;
use Prospecter\Core\SharedKernel\Domain\PersonId;

/**
 * Reads the exchanges that the in-memory repository saved, as the adapter reads the table's items.
 */
final readonly class InMemoryExchangeDetailRetriever implements ExchangeDetailRetriever
{
    public function __construct(private InMemoryDatabase $database)
    {
    }

    public function get(ExchangeId $id, Prospector $reader, \DateTimeImmutable $asOf, ?\DateTimeImmutable $movedFrom = null): ExchangeDetail
    {
        $exchange = $this->database->exchange((string) $id) ?? throw UnknownExchange::identifiedBy($id);
        $state = StateOf::the($exchange);
        $facts = $state->object('facts', ExchangeFacts::class);
        $correction = $state->objectOrNull('lastCorrection', Correction::class);
        $madeConform = $state->objectOrNull('madeConform', Correction::class);
        $contactForgotten = $state->bool('contactForgotten');
        $attachments = $exchange->attachments();
        $read = ExchangeReading::readsTheAttachments($reader) ? $attachments : [];
        $attachmentsToMakeConform = ExchangeConformity::areAttachmentsToMakeConform($contactForgotten);

        return new ExchangeDetail(
            id: $exchange->id()->value,
            accountId: $exchange->accountId()->value,
            personId: $state->objectOrNull('contact', PersonId::class)?->value,
            contactPresence: ContactPresence::ofTheContact(null !== $state->objectOrNull('contact', PersonId::class), $contactForgotten)?->value,
            channel: $facts->channel->value,
            direction: $facts->direction->value,
            occurredAt: $facts->occurredAt,
            status: $state->object('status', ExchangeStatus::class)->value,
            canceller: $state->objectOrNull('cancellation', Cancellation::class)?->canceller?->value,
            durationInMinutes: $facts->duration?->minutes,
            text: $facts->text,
            place: $facts->place,
            link: $facts->link?->url,
            linkAwaited: Exchange::awaitsALink($facts->linkRequested, $state->object('status', ExchangeStatus::class)),
            textFlagged: ExchangeReading::isTextFlagged(null !== $facts->text, $reader),
            textToMakeConform: ExchangeConformity::isTextToMakeConform(null !== $facts->text, $contactForgotten, null !== $madeConform),
            toMakeConform: ExchangeConformity::isToMakeConform($contactForgotten, null !== $madeConform, [] !== $attachments),
            organizationIds: array_map(static fn (OrganizationId $organization): string => $organization->value, $state->listOf('keptFor', OrganizationId::class)),
            createdBy: $state->object('createdBy', MemberId::class)->value,
            createdAt: $state->object('createdAt', \DateTimeImmutable::class),
            correctedBy: $correction?->by->value,
            correctedAt: $correction?->at,
            madeConformBy: $madeConform?->by->value,
            madeConformAt: $madeConform?->at,
            attachments: array_map(static fn (Attachment $attachment): AttachmentDetail => new AttachmentDetail($attachment->id->value, $attachment->name, $attachment->size, $attachment->mediaType, $attachmentsToMakeConform), $read),
            hiddenAttachments: count($attachments) - count($read),
            possibleIntents: array_map(static fn (ExchangeIntent $intent): string => $intent->name, ExchangePossibilities::given(new ExchangeSituation(count($attachments), ExchangeConformity::awaitsBeingMadeConform($contactForgotten, null !== $madeConform), $state->object('status', ExchangeStatus::class), $facts->occurredAt <= $asOf, $state->object('createdBy', MemberId::class)->equals($reader->id), $facts->channel, null !== $movedFrom && Exchange::isAMomentToCome($movedFrom, $asOf)))),
            campaignId: $state->objectOrNull('campaign', CampaignId::class)?->value,
            bookedByLink: $state->bool('bookedByLink'),
            importedFrom: $state->objectOrNull('importedFrom', ImportedEmail::class)?->provider,
            providerMessageId: $state->objectOrNull('importedFrom', ImportedEmail::class)?->providerMessageId,
            createdByAnAI: Origin::Ai === $state->object('origin', Origin::class),
        );
    }
}
