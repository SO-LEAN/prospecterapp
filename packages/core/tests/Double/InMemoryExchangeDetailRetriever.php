<?php

declare(strict_types=1);

namespace Tests\Core\Double;

use Prospecter\Core\Prospecting\Application\ConsultExchange\AttachmentDetail;
use Prospecter\Core\Prospecting\Application\ConsultExchange\ExchangeDetail;
use Prospecter\Core\Prospecting\Application\Port\ExchangeDetailRetriever;
use Prospecter\Core\Prospecting\Domain\Exception\UnknownExchange;
use Prospecter\Core\Prospecting\Domain\Model\Attachment;
use Prospecter\Core\Prospecting\Domain\Model\ContactPresence;
use Prospecter\Core\Prospecting\Domain\Model\ExchangeId;
use Prospecter\Core\Prospecting\Domain\Model\ExchangeIntent;
use Prospecter\Core\Prospecting\Domain\Model\Prospector;
use Prospecter\Core\Prospecting\Domain\Policy\ExchangeConformity;
use Prospecter\Core\Prospecting\Domain\Policy\ExchangePossibilities;
use Prospecter\Core\Prospecting\Domain\Policy\ExchangeReading;
use Prospecter\Core\SharedKernel\Domain\OrganizationId;

/**
 * Reads the exchanges the in-memory repository saved, as the adapter reads the items of the table.
 */
final readonly class InMemoryExchangeDetailRetriever implements ExchangeDetailRetriever
{
    public function __construct(private InMemoryDatabase $database)
    {
    }

    public function get(ExchangeId $id, Prospector $reader): ExchangeDetail
    {
        $exchange = $this->database->exchange((string) $id) ?? throw UnknownExchange::identifiedBy($id);
        $state = StateOf::the($exchange);
        $facts = $state['facts'];
        $correction = $state['lastCorrection'];
        $madeConform = $state['madeConform'];
        $contactForgotten = $state['contactForgotten'];
        $attachments = $exchange->attachments();
        $read = ExchangeReading::readsTheAttachments($reader) ? $attachments : [];
        $attachmentsToMakeConform = ExchangeConformity::areAttachmentsToMakeConform($contactForgotten);

        return new ExchangeDetail(
            id: $exchange->id()->value,
            accountId: $exchange->accountId()->value,
            personId: $state['contact']?->value,
            contactPresence: ContactPresence::ofTheContact(null !== $state['contact'], $contactForgotten)?->value,
            channel: $facts->channel->value,
            direction: $facts->direction->value,
            missed: $facts->missed,
            occurredAt: $facts->occurredAt,
            text: $facts->text,
            textFlagged: ExchangeReading::isTextFlagged(null !== $facts->text, $reader),
            textToMakeConform: ExchangeConformity::isTextToMakeConform(null !== $facts->text, $contactForgotten, null !== $madeConform),
            toMakeConform: ExchangeConformity::isToMakeConform($contactForgotten, null !== $madeConform, [] !== $attachments),
            organizationIds: array_map(static fn (OrganizationId $organization): string => $organization->value, $state['keptFor']),
            createdBy: $state['createdBy']->value,
            createdAt: $state['createdAt'],
            correctedBy: $correction?->by->value,
            correctedAt: $correction?->at,
            madeConformBy: $madeConform?->by->value,
            madeConformAt: $madeConform?->at,
            attachments: array_map(static fn (Attachment $attachment): AttachmentDetail => new AttachmentDetail($attachment->id->value, $attachment->name, $attachment->size, $attachment->mediaType, $attachmentsToMakeConform), $read),
            hiddenAttachments: count($attachments) - count($read),
            possibleIntents: array_map(static fn (ExchangeIntent $intent): string => $intent->name, ExchangePossibilities::given(count($attachments), ExchangeConformity::awaitsBeingMadeConform($contactForgotten, null !== $madeConform))),
        );
    }
}
