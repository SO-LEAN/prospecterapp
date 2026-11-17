<?php

declare(strict_types=1);

namespace Prospecter\Core\Campaigns\Domain\Model;

use Prospecter\Core\Campaigns\Domain\Event\CampaignDiscarded;
use Prospecter\Core\Campaigns\Domain\Event\CampaignPrepared;
use Prospecter\Core\Campaigns\Domain\Event\CampaignRevised;
use Prospecter\Core\Campaigns\Domain\Policy\CampaignPossibilities;
use Prospecter\Core\SharedKernel\Domain\AccountId;
use Prospecter\Core\SharedKernel\Domain\Exception\NotPossibleInItsState;
use Prospecter\Core\SharedKernel\Domain\MemberId;
use Prospecter\Core\SharedKernel\Domain\RecordsEvents;
use Prospecter\Core\SharedKernel\Domain\ReleasesEvents;

/**
 * Aggregate root: a campaign of communication the team of an account prepares (ADR 0144), its members contacting each
 * person it targets themselves, by its channel, the message it keeps reminding them; Prospecter sends nothing. Shared
 * by the team, prepared by its author. A draft targets the contacts carrying its tags as they are now.
 */
final class Campaign implements ReleasesEvents
{
    use RecordsEvents;

    private CampaignStatus $status = CampaignStatus::Draft;
    private \DateTimeImmutable $modifiedAt;

    private function __construct(
        private readonly CampaignId $id,
        private readonly AccountId $accountId,
        private readonly MemberId $createdBy,
        private CampaignName $name,
        private Targeting $targeting,
        private MessageTemplate $message,
        private readonly \DateTimeImmutable $createdAt,
    ) {
        $this->modifiedAt = $createdAt;
    }

    public static function prepare(CampaignId $id, AccountId $accountId, CampaignName $name, Targeting $targeting, MessageTemplate $message, MemberId $by, \DateTimeImmutable $at): self
    {
        $campaign = new self($id, $accountId, $by, $name, $targeting, $message, $at);
        $campaign->recordThat(new CampaignPrepared($accountId, $by, $id, $at));

        return $campaign;
    }

    public function id(): CampaignId
    {
        return $this->id;
    }

    public function accountId(): AccountId
    {
        return $this->accountId;
    }

    /**
     * @throws NotPossibleInItsState
     */
    public function revise(CampaignName $name, Targeting $targeting, MessageTemplate $message, MemberId $by, \DateTimeImmutable $at): void
    {
        $this->ensurePossible(CampaignIntent::ReviseCampaign);
        $this->name = $name;
        $this->targeting = $targeting;
        $this->message = $message;
        $this->modifiedAt = $at;
        $this->recordThat(new CampaignRevised($this->accountId, $by, $this->id, $at));
    }

    /**
     * Discarded everywhere, once the repository removed it.
     *
     * @throws NotPossibleInItsState
     */
    public function discard(MemberId $by, \DateTimeImmutable $at): void
    {
        $this->ensurePossible(CampaignIntent::DiscardCampaign);
        $this->recordThat(new CampaignDiscarded($this->accountId, $by, $this->id, $at));
    }

    #[\NoDiscard]
    public function messageFor(Addressee $addressee): RenderedMessage
    {
        return $this->message->renderedFor($addressee);
    }

    private function ensurePossible(CampaignIntent $intent): void
    {
        if (!in_array($intent, CampaignPossibilities::given($this->status), true)) {
            throw NotPossibleInItsState::because('Only a draft campaign is changed or discarded');
        }
    }
}
