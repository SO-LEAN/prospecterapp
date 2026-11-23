<?php

declare(strict_types=1);

namespace App\Campaigns\Persistence;

use App\Shared\Persistence\Documents;
use App\Shared\Persistence\Hydrator;
use App\Shared\Typed;
use Prospecter\Core\Campaigns\Application\CampaignSheet;
use Prospecter\Core\Campaigns\Application\RecipientInSheet;
use Prospecter\Core\Campaigns\Domain\Model\Campaign;
use Prospecter\Core\Campaigns\Domain\Model\CampaignId;
use Prospecter\Core\Campaigns\Domain\Model\CampaignIntent;
use Prospecter\Core\Campaigns\Domain\Model\CampaignName;
use Prospecter\Core\Campaigns\Domain\Model\CampaignStatus;
use Prospecter\Core\Campaigns\Domain\Model\Channel;
use Prospecter\Core\Campaigns\Domain\Model\ContactId;
use Prospecter\Core\Campaigns\Domain\Model\DailyCap;
use Prospecter\Core\Campaigns\Domain\Model\MessageMode;
use Prospecter\Core\Campaigns\Domain\Model\MessageTemplate;
use Prospecter\Core\Campaigns\Domain\Model\Recipient;
use Prospecter\Core\Campaigns\Domain\Model\Recipients;
use Prospecter\Core\Campaigns\Domain\Model\RecipientStatus;
use Prospecter\Core\Campaigns\Domain\Model\Tag;
use Prospecter\Core\Campaigns\Domain\Model\TagSelection;
use Prospecter\Core\Campaigns\Domain\Model\Targeting;
use Prospecter\Core\Campaigns\Domain\Rule\CampaignPossibilities;
use Prospecter\Core\Campaigns\Domain\Rule\CampaignProgress;
use Prospecter\Core\SharedKernel\Domain\AccountId;
use Prospecter\Core\SharedKernel\Domain\MemberId;

/**
 * A campaign as its item keeps it (ADR 0048, 0144):
 *
 * - its tags by name, without their `#`. The targeted tags are derived from them and from the channel, and are never
 *   kept;
 * - once frozen, its recipients in the order added, none in a draft. Each one has its id and status. A processed
 *   recipient records whether the call went through, the exchange that processed them (ADR 0145, 0152), and when.
 *   A dismissed one records the reason;
 * - when it was started, stopped and ended, null before;
 * - how an AI treats its message, `asIs` for an item written before the mode was kept;
 * - its daily cap.
 */
final class CampaignDocument
{
    /**
     * @return array<string, mixed>
     */
    public static function of(Campaign $campaign): array
    {
        $state = Hydrator::stateOf($campaign);
        $targeting = Typed::object($state['targeting'], Targeting::class);
        $message = Typed::object($state['message'], MessageTemplate::class);

        return [
            'id' => Typed::object($state['id'], CampaignId::class)->value,
            'accountId' => Typed::object($state['accountId'], AccountId::class)->value,
            'createdBy' => Typed::object($state['createdBy'], MemberId::class)->value,
            'name' => Typed::object($state['name'], CampaignName::class)->value,
            'channel' => $targeting->channel->value,
            'tags' => array_map(static fn (Tag $tag): string => $tag->name, $targeting->chosen->tags),
            'message' => $message->text,
            'subject' => $message->subject,
            'messageMode' => $message->mode->value,
            'status' => Typed::object($state['status'], CampaignStatus::class)->value,
            'recipients' => array_map(self::recipientDocument(...), Typed::object($state['recipients'], Recipients::class)->recipients),
            'createdAt' => Documents::moment(Typed::object($state['createdAt'], \DateTimeImmutable::class)),
            'modifiedAt' => Documents::moment(Typed::object($state['modifiedAt'], \DateTimeImmutable::class)),
            'startedAt' => Documents::momentOrNone(Typed::objectOrNull($state['startedAt'], \DateTimeImmutable::class)),
            'stoppedAt' => Documents::momentOrNone(Typed::objectOrNull($state['stoppedAt'], \DateTimeImmutable::class)),
            'endedAt' => Documents::momentOrNone(Typed::objectOrNull($state['endedAt'], \DateTimeImmutable::class)),
            'dailyCap' => Typed::objectOrNull($state['dailyCap'], DailyCap::class)?->sends,
        ];
    }

    /**
     * Restores the campaign from its item's untyped document.
     */
    public static function campaign(mixed $document): Campaign
    {
        $document = Typed::document($document);
        $channel = Channel::from(Typed::string($document['channel']));

        return Hydrator::restore(Campaign::class, [
            'id' => new CampaignId(Typed::string($document['id'])),
            'accountId' => new AccountId(Typed::string($document['accountId'])),
            'createdBy' => new MemberId(Typed::string($document['createdBy'])),
            'name' => CampaignName::of(Typed::string($document['name'])),
            'targeting' => self::targetingOf($document),
            'message' => MessageTemplate::of(Typed::string($document['message']), Typed::stringOrNull($document['subject']), $channel, MessageMode::from(self::messageModeOf($document))),
            'status' => CampaignStatus::from(Typed::string($document['status'])),
            'recipients' => Hydrator::restore(Recipients::class, ['recipients' => array_map(
                self::recipient(...),
                self::recipientsOf($document),
            )]),
            'createdAt' => Documents::momentOf(Typed::string($document['createdAt'])),
            'modifiedAt' => Documents::momentOf(Typed::string($document['modifiedAt'])),
            'startedAt' => self::momentOfOrNone($document, 'startedAt'),
            'stoppedAt' => self::momentOfOrNone($document, 'stoppedAt'),
            'endedAt' => self::momentOfOrNone($document, 'endedAt'),
            'dailyCap' => DailyCap::orNone(self::dailyCapOf($document)),
        ]);
    }

    /**
     * The read model from its item's untyped document, without restoring the campaign (ADR 0088).
     */
    public static function sheet(mixed $document): CampaignSheet
    {
        $document = Typed::document($document);
        $targeting = self::targetingOf($document);
        $status = CampaignStatus::from(Typed::string($document['status']));
        $recipients = array_map(self::recipient(...), self::recipientsOf($document));
        $statuses = array_map(static fn (Recipient $recipient): RecipientStatus => $recipient->status, $recipients);

        return new CampaignSheet(
            id: Typed::string($document['id']),
            accountId: Typed::string($document['accountId']),
            name: Typed::string($document['name']),
            channel: Typed::string($document['channel']),
            tags: self::named($targeting->chosen),
            targetedTags: self::named($targeting->targeted()),
            message: Typed::string($document['message']),
            subject: Typed::stringOrNull($document['subject']),
            messageMode: self::messageModeOf($document),
            status: $status->value,
            followsTheTags: $status->followsTheTags(),
            running: $status->isRunning(),
            processed: CampaignProgress::processedAmong($statuses),
            dismissed: CampaignProgress::dismissedAmong($statuses),
            recipients: array_combine(
                array_map(static fn (Recipient $recipient): string => $recipient->contact->value, $recipients),
                array_map(static fn (Recipient $recipient): RecipientInSheet => new RecipientInSheet($recipient->status->value, $recipient->isPending(), $recipient->missed, $recipient->reason, $recipient->exchangeId, $recipient->erased, $recipient->processedAt), $recipients),
            ),
            authorId: Typed::string($document['createdBy']),
            possibleIntents: array_map(static fn (CampaignIntent $intent): string => $intent->name, CampaignPossibilities::given($status, [] !== $recipients)),
            createdAt: Documents::momentOf(Typed::string($document['createdAt'])),
            modifiedAt: Documents::momentOf(Typed::string($document['modifiedAt'])),
            startedAt: self::momentOfOrNone($document, 'startedAt'),
            stoppedAt: self::momentOfOrNone($document, 'stoppedAt'),
            endedAt: self::momentOfOrNone($document, 'endedAt'),
            dailyCap: self::dailyCapOf($document),
        );
    }

    /**
     * @param array<string, mixed> $document
     *
     * @return list<array<string, mixed>> empty for a campaign saved before campaigns could be frozen
     */
    private static function recipientsOf(array $document): array
    {
        return Typed::documents($document['recipients'] ?? []);
    }

    /**
     * @return array{contactId: string, status: string, missed: bool, reason: ?string, exchangeId: ?string, erased: bool, processedAt: ?string}
     */
    private static function recipientDocument(Recipient $recipient): array
    {
        return ['contactId' => $recipient->contact->value, 'status' => $recipient->status->value, 'missed' => $recipient->missed, 'reason' => $recipient->reason, 'exchangeId' => $recipient->exchangeId, 'erased' => $recipient->erased, 'processedAt' => Documents::momentOrNone($recipient->processedAt)];
    }

    /**
     * Reads a recipient. An item written by an older version lacks some fields:
     *
     * - saved before campaigns ran: without whether it was missed or why it was dismissed;
     * - processed before recipients kept their exchange: without its exchange (ADR 0152);
     * - saved before an erased recipient kept what the team did: erased by its status alone;
     * - saved before campaigns had a daily cap: without when it was processed.
     *
     * @param array<string, mixed> $document
     */
    private static function recipient(array $document): Recipient
    {
        $status = RecipientStatus::from(Typed::string($document['status']));

        return new Recipient(new ContactId(Typed::string($document['contactId'])), $status, Typed::bool($document['missed'] ?? false), Typed::stringOrNull($document['reason'] ?? null), Typed::stringOrNull($document['exchangeId'] ?? null), Typed::bool($document['erased'] ?? RecipientStatus::Erased === $status), self::momentOfOrNone($document, 'processedAt'));
    }

    /**
     * Null for a campaign saved before campaigns had a daily cap.
     *
     * @param array<string, mixed> $document
     */
    private static function dailyCapOf(array $document): ?int
    {
        return Typed::intOrNull($document['dailyCap'] ?? null);
    }

    /**
     * `asIs` for a campaign saved before an AI could adapt its message.
     *
     * @param array<string, mixed> $document
     */
    private static function messageModeOf(array $document): string
    {
        return Typed::string($document['messageMode'] ?? MessageMode::AsIs->value);
    }

    /**
     * Null for a campaign saved before campaigns could be started.
     *
     * @param array<string, mixed> $document
     */
    private static function momentOfOrNone(array $document, string $field): ?\DateTimeImmutable
    {
        return Documents::momentOfOrNone(Typed::stringOrNull($document[$field] ?? null));
    }

    /**
     * @param array<string, mixed> $document
     */
    private static function targetingOf(array $document): Targeting
    {
        return Targeting::by(Channel::from(Typed::string($document['channel'])), array_map(static fn (string $name): string => Tag::MARK.$name, Typed::strings($document['tags'])));
    }

    /**
     * @return list<string>
     */
    private static function named(TagSelection $selection): array
    {
        return array_map(strval(...), $selection->tags);
    }
}
