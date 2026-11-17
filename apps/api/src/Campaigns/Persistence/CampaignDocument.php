<?php

namespace App\Campaigns\Persistence;

use App\Shared\Persistence\Documents;
use App\Shared\Persistence\Hydrator;
use Prospecter\Core\Campaigns\Application\CampaignSheet;
use Prospecter\Core\Campaigns\Domain\Model\Campaign;
use Prospecter\Core\Campaigns\Domain\Model\CampaignId;
use Prospecter\Core\Campaigns\Domain\Model\CampaignIntent;
use Prospecter\Core\Campaigns\Domain\Model\CampaignName;
use Prospecter\Core\Campaigns\Domain\Model\CampaignStatus;
use Prospecter\Core\Campaigns\Domain\Model\Channel;
use Prospecter\Core\Campaigns\Domain\Model\MessageTemplate;
use Prospecter\Core\Campaigns\Domain\Model\Tag;
use Prospecter\Core\Campaigns\Domain\Model\TagSelection;
use Prospecter\Core\Campaigns\Domain\Model\Targeting;
use Prospecter\Core\Campaigns\Domain\Policy\CampaignPossibilities;
use Prospecter\Core\SharedKernel\Domain\AccountId;
use Prospecter\Core\SharedKernel\Domain\MemberId;

/**
 * A campaign as its item keeps it (ADR 0048, 0144): its tags by their names without their mark, those of its target
 * derived from them and from its channel, never kept.
 */
final class CampaignDocument
{
    /**
     * @return array<string, mixed>
     */
    public static function of(Campaign $campaign): array
    {
        $state = Hydrator::stateOf($campaign);
        $targeting = $state['targeting'];

        return [
            'id' => (string) $state['id'],
            'accountId' => (string) $state['accountId'],
            'createdBy' => (string) $state['createdBy'],
            'name' => $state['name']->value,
            'channel' => $targeting->channel->value,
            'tags' => array_map(static fn (Tag $tag): string => $tag->name, $targeting->chosen->tags),
            'message' => $state['message']->text,
            'subject' => $state['message']->subject,
            'status' => $state['status']->value,
            'createdAt' => Documents::moment($state['createdAt']),
            'modifiedAt' => Documents::moment($state['modifiedAt']),
        ];
    }

    /**
     * @param array<string, mixed> $document
     */
    public static function campaign(array $document): Campaign
    {
        $channel = Channel::from($document['channel']);

        return Hydrator::restore(Campaign::class, [
            'id' => new CampaignId($document['id']),
            'accountId' => new AccountId($document['accountId']),
            'createdBy' => new MemberId($document['createdBy']),
            'name' => CampaignName::of($document['name']),
            'targeting' => self::targetingOf($document),
            'message' => MessageTemplate::of($document['message'], $document['subject'], $channel),
            'status' => CampaignStatus::from($document['status']),
            'createdAt' => Documents::momentOf($document['createdAt']),
            'modifiedAt' => Documents::momentOf($document['modifiedAt']),
        ]);
    }

    /**
     * The read model, without restoring the campaign (ADR 0088).
     *
     * @param array<string, mixed> $document
     */
    public static function sheet(array $document): CampaignSheet
    {
        $targeting = self::targetingOf($document);

        return new CampaignSheet(
            id: $document['id'],
            accountId: $document['accountId'],
            name: $document['name'],
            channel: $document['channel'],
            tags: self::named($targeting->chosen),
            targetedTags: self::named($targeting->targeted()),
            message: $document['message'],
            subject: $document['subject'],
            status: $document['status'],
            authorId: $document['createdBy'],
            possibleIntents: array_map(static fn (CampaignIntent $intent): string => $intent->name, CampaignPossibilities::given(CampaignStatus::from($document['status']))),
            createdAt: Documents::momentOf($document['createdAt']),
            modifiedAt: Documents::momentOf($document['modifiedAt']),
        );
    }

    /**
     * @param array<string, mixed> $document
     */
    private static function targetingOf(array $document): Targeting
    {
        return Targeting::by(Channel::from($document['channel']), array_map(static fn (string $name): string => Tag::MARK.$name, $document['tags']));
    }

    /**
     * @return list<string>
     */
    private static function named(TagSelection $selection): array
    {
        return array_map(strval(...), $selection->tags);
    }
}
