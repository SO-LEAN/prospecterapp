<?php

declare(strict_types=1);

namespace Tests\Core\Acceptance\Context;

use Behat\Behat\Context\Context;
use Behat\Gherkin\Node\TableNode;
use Behat\Step\Given;
use Behat\Step\Then;
use Behat\Step\When;
use Prospecter\Core\Accounts\Domain\Model\Role;
use Prospecter\Core\Campaigns\Application\AddRecipient\AddRecipient;
use Prospecter\Core\Campaigns\Application\AddRecipientsByTags\AddRecipientsByTags;
use Prospecter\Core\Campaigns\Application\CampaignSheet;
use Prospecter\Core\Campaigns\Application\ConsultCampaign\ConsultCampaign;
use Prospecter\Core\Campaigns\Application\DiscardCampaign\DiscardCampaign;
use Prospecter\Core\Campaigns\Application\EraseCampaign\EraseCampaign;
use Prospecter\Core\Campaigns\Application\FreezeCampaign\FreezeCampaign;
use Prospecter\Core\Campaigns\Application\ListCampaigns\ListCampaigns;
use Prospecter\Core\Campaigns\Application\PrepareCampaign\PrepareCampaign;
use Prospecter\Core\Campaigns\Application\RebuildTags\RebuildTags;
use Prospecter\Core\Campaigns\Application\RemoveRecipient\RemoveRecipient;
use Prospecter\Core\Campaigns\Application\RenderCampaignMessage\CampaignMessage;
use Prospecter\Core\Campaigns\Application\RenderCampaignMessage\RenderCampaignMessage;
use Prospecter\Core\Campaigns\Application\ReviseCampaign\ReviseCampaign;
use Prospecter\Core\Campaigns\Application\SearchContactsByTags\SearchContactsByTags;
use Prospecter\Core\Campaigns\Application\StartCampaign\StartCampaign;
use Prospecter\Core\Campaigns\Application\StopCampaign\StopCampaign;
use Prospecter\Core\Campaigns\Domain\Model\Campaign;
use Prospecter\Core\Campaigns\Domain\Model\CampaignName;
use Prospecter\Core\Campaigns\Domain\Model\Channel;
use Prospecter\Core\Campaigns\Domain\Model\MessageTemplate;
use Prospecter\Core\Campaigns\Domain\Model\Targeting;
use Prospecter\Core\PersonalData\Application\ChangePersonContactDetails\ChangePersonContactDetails;
use Prospecter\Core\PersonalData\Application\ConsultPersons\ConsultPersons;
use Prospecter\Core\PersonalData\Application\PersonCard;
use Prospecter\Core\SharedKernel\Domain\AccountId;
use Prospecter\Core\SharedKernel\Domain\CampaignId;
use Prospecter\Core\SharedKernel\Domain\MemberId;
use Tests\Core\Acceptance\Expect;
use Tests\Core\Acceptance\Vocabulary;

/**
 * The campaigns a team prepares, whom they target and the message they render for each contact (Campaigns,
 * ADR 0144).
 */
final class CampaignContext implements Context
{
    use SharesTheScenario;

    private const array CHANNELS = ['E-mail' => 'email', 'Appel' => 'call', 'SMS' => 'sms', 'LinkedIn' => 'linkedIn'];
    private const array STATUSES = ['draft' => 'brouillon', 'frozen' => 'figée', 'started' => 'démarrée', 'stopped' => 'arrêtée'];
    private const array RECIPIENT_STATUSES = ['pending' => 'à traiter', 'erased' => 'plus à traiter'];
    private const string NOBODY_CARRIES = '#PERSONNE';
    private const string A_MESSAGE = 'Bonjour {prénom}';
    private const string REVISED_MESSAGE = 'Bonjour {prénom}, je reviens vers vous';
    private const string SOME_TAGS = '#ESN';

    /** @var array<string, string> the identities of the campaigns, by their names */
    private array $campaignIds = [];
    private ?string $current = null;
    private ?ReviseCampaign $revision = null;
    private ?CampaignMessage $rendered = null;
    /** @var list<CampaignSheet> */
    private array $listed = [];
    /** @var array<string, int> */
    private array $targeted = [];

    #[When('je prépare la campagne :name :')]
    public function iPrepareTheCampaign(string $name, TableNode $table): void
    {
        $values = $table->getRowsHash();
        $this->prepare($name, $values['canal'], $values['tags'], $values['message'], $values['objet'] ?? null);
    }

    #[When('je prépare la campagne :name avec le canal :channel et les tags :tags')]
    public function iPrepareTheCampaignBy(string $name, string $channel, string $tags): void
    {
        $this->prepare($name, $channel, $tags, self::A_MESSAGE);
    }

    #[When('je prépare une campagne avec le canal :channel')]
    public function iPrepareACampaignBy(string $channel): void
    {
        $this->prepare('Campagne', $channel, self::SOME_TAGS, self::A_MESSAGE);
    }

    #[When('je prépare la campagne :name sans tag')]
    public function iPrepareTheCampaignWithoutTag(string $name): void
    {
        $this->prepare($name, 'E-mail', '', self::A_MESSAGE);
    }

    #[When('je prépare une campagne dont le nom fait :length caractères')]
    public function iPrepareACampaignNamedAtLength(int $length): void
    {
        $this->prepare(str_repeat('a', $length), 'E-mail', self::SOME_TAGS, self::A_MESSAGE);
    }

    #[When('je prépare une campagne dont le message fait :length caractères')]
    public function iPrepareACampaignWithAMessageOf(int $length): void
    {
        $this->prepare('Campagne', 'E-mail', self::SOME_TAGS, str_repeat('é', $length));
    }

    #[When('je prépare la campagne :name par SMS avec l\'objet :subject')]
    public function iPrepareTheCampaignBySmsWithASubject(string $name, string $subject): void
    {
        $this->prepare($name, 'SMS', self::SOME_TAGS, self::A_MESSAGE, $subject);
    }

    #[Given('la campagne :name en brouillon')]
    public function theDraft(string $name): void
    {
        $this->arrange($name, 'E-mail', self::SOME_TAGS, self::A_MESSAGE);
    }

    #[Given('la campagne :name en brouillon cible les tags :tags')]
    public function theDraftTargeting(string $name, string $tags): void
    {
        $this->arrange($name, 'E-mail', $tags, self::A_MESSAGE);
    }

    #[Given('la campagne :name en brouillon cible les tags :tags par :channel')]
    public function theDraftTargetingBy(string $name, string $tags, string $channel): void
    {
        $this->arrange($name, $channel, $tags, self::A_MESSAGE);
    }

    #[Given('la campagne :name a le message :message')]
    public function theCampaignHasTheMessage(string $name, string $message): void
    {
        $this->arrange($name, 'E-mail', self::SOME_TAGS, $message);
    }

    #[Given('la campagne :name a l\'objet :subject et le message :message')]
    public function theCampaignHasTheSubjectAndMessage(string $name, string $subject, string $message): void
    {
        $this->arrange($name, 'E-mail', self::SOME_TAGS, $message, $subject);
    }

    #[Given('un autre prospecteur de :account a préparé la campagne :name')]
    public function anotherProspectorPrepared(string $account, string $name): void
    {
        $author = $this->scenario->aMemberOf('un autre prospecteur', $account, Role::User, Role::Prospector, Role::PersonalData);
        $this->arrange($name, 'E-mail', self::SOME_TAGS, self::A_MESSAGE, null, $author->accountId(), $author->id());
    }

    #[Given('un prospecteur de :account a préparé la campagne :name')]
    public function aProspectorPrepared(string $account, string $name): void
    {
        $accountId = $this->scenario->accountIdOf($account);
        $this->arrange($name, 'E-mail', self::SOME_TAGS, self::A_MESSAGE, null, $accountId, $this->scenario->prospectorOf($accountId)->id);
    }

    #[When(':contact reçoit l\'adresse e-mail :email')]
    public function theContactGetsTheEmail(string $contact, string $email): void
    {
        $this->application->dispatch(new ChangePersonContactDetails($this->application->signedIn(), $this->scenario->personIdOf($contact), $email, null, null, null, $this->application->now()));
    }

    #[When('je change ses tags en :tags, son canal en :channel et son message')]
    public function iReviseIt(string $tags, string $channel): void
    {
        $this->revision = new ReviseCampaign($this->application->signedIn(), $this->currentId(), $this->currentName(), self::CHANNELS[$channel], explode(' ', $tags), self::REVISED_MESSAGE, null, $this->application->now());
        $this->application->dispatch($this->revision);
    }

    #[When('je supprime ce brouillon')]
    public function iDiscardIt(): void
    {
        $this->application->dispatch(new DiscardCampaign($this->application->signedIn(), $this->currentId(), $this->application->now()));
    }

    #[Given('la campagne :name en brouillon, canal :channel, tags :tags')]
    public function theDraftByWithTags(string $name, string $channel, string $tags): void
    {
        $this->arrange($name, $channel, $tags, self::A_MESSAGE);
    }

    #[Given('la campagne :name en brouillon ne cible personne')]
    public function theDraftTargetingNobody(string $name): void
    {
        $this->arrange($name, 'E-mail', self::NOBODY_CARRIES, self::A_MESSAGE);
    }

    #[Given('la campagne :name est figée')]
    public function theFrozenCampaign(string $name): void
    {
        $this->iFreezeTheCampaign($name);
        $this->itIsFrozen();
    }

    #[Given('la campagne :name est figée avec :one et :other')]
    public function theCampaignFrozenWith(string $name, string $one, string $other): void
    {
        $this->theFrozenCampaign($name);
        $this->iAddTheContact($other);
        Expect::same([$this->scenario->personIdOf($one), $this->scenario->personIdOf($other)], array_keys($this->sheet()->recipients));
    }

    #[Given('la campagne :name figée, canal :channel, avec :contact')]
    public function theCampaignFrozenByWith(string $name, string $channel, string $contact): void
    {
        $this->application->dispatch(new RebuildTags(null));
        $this->arrange($name, $channel, self::SOME_TAGS, self::A_MESSAGE);
        $this->theFrozenCampaign($name);
        $this->itsRecipientsAre($contact);
    }

    #[When('je démarre la campagne :name')]
    public function iStartTheCampaign(string $name): void
    {
        $this->current = $name;
        $this->application->dispatch(new StartCampaign($this->application->signedIn(), $this->currentId(), $this->application->now()));
    }

    #[Given('la campagne :name est démarrée')]
    public function theStartedCampaign(string $name): void
    {
        $this->iStartTheCampaign($name);
        Expect::same('started', $this->sheet()->status);
    }

    #[When('j\'arrête la campagne :name')]
    public function iStopTheCampaign(string $name): void
    {
        $this->current = $name;
        $this->application->dispatch(new StopCampaign($this->application->signedIn(), $this->currentId(), $this->application->now()));
    }

    #[Given('la campagne :name est arrêtée')]
    public function theStoppedCampaign(string $name): void
    {
        $this->theStartedCampaign($name);
        $this->iStopTheCampaign($name);
        Expect::same('stopped', $this->sheet()->status);
    }

    #[When('l\'exploitation efface la campagne :name')]
    public function theOperationsEraseTheCampaign(string $name): void
    {
        $this->current = $name;
        $this->application->dispatch(new EraseCampaign($this->currentId()));
    }

    #[Then('/^elle est démarrée le (?<moment>.+)$/')]
    public function itIsStartedOn(string $moment): void
    {
        $sheet = $this->sheet();

        Expect::same(['started', Vocabulary::frenchMoment($moment)->getTimestamp()], [$sheet->status, $sheet->startedAt?->getTimestamp()]);
    }

    #[Then('/^elle est arrêtée le (?<moment>.+)$/')]
    public function itIsStoppedOn(string $moment): void
    {
        $sheet = $this->sheet();

        Expect::same(['stopped', Vocabulary::frenchMoment($moment)->getTimestamp()], [$sheet->status, $sheet->stoppedAt?->getTimestamp()]);
    }

    #[Then('elle est toujours dans la liste des campagnes')]
    public function itIsStillListed(): void
    {
        $this->iListTheCampaigns();
        Expect::same([$this->currentName()], array_map(static fn (CampaignSheet $campaign): string => $campaign->name, $this->listed));
    }

    #[Then('son destinataire est « Contact effacé », plus à traiter')]
    public function itsRecipientIsAnErasedContact(): void
    {
        $recipients = $this->sheet()->recipients;
        $read = $this->application->consultPersons(new ConsultPersons($this->application->signedIn(), array_map(strval(...), array_keys($recipients)))) ?? [];

        Expect::same(['plus à traiter'], array_values(array_map(static fn (string $status): string => self::RECIPIENT_STATUSES[$status], $recipients)));
        Expect::same([], $read);
    }

    #[When('je fige la campagne :name')]
    public function iFreezeTheCampaign(string $name): void
    {
        $this->current = $name;
        $this->application->dispatch(new FreezeCampaign($this->application->signedIn(), $this->currentId(), $this->application->now()));
    }

    #[When('je change ses tags en :tags')]
    public function iChangeItsTags(string $tags): void
    {
        $sheet = $this->sheet();
        $this->application->dispatch(new ReviseCampaign($this->application->signedIn(), $sheet->id, $sheet->name, $sheet->channel, self::listed($tags), $sheet->message, $sheet->subject, $this->application->now()));
    }

    #[When('je la renomme :name et change son message')]
    public function iRenameItAndChangeItsMessage(string $name): void
    {
        $sheet = $this->sheet();
        $this->campaignIds[$name] = $sheet->id;
        $this->revision = new ReviseCampaign($this->application->signedIn(), $sheet->id, $name, $sheet->channel, $sheet->tags, self::REVISED_MESSAGE, $sheet->subject, $this->application->now());
        $this->application->dispatch($this->revision);
    }

    #[When('j\'y ajoute les contacts tagués :tags')]
    public function iAddTheContactsTagged(string $tags): void
    {
        $this->application->dispatch(new AddRecipientsByTags($this->application->signedIn(), $this->currentId(), self::listed($tags), $this->application->now()));
    }

    #[When('j\'y ajoute :contact')]
    public function iAddTheContact(string $contact): void
    {
        $this->application->dispatch(new AddRecipient($this->application->signedIn(), $this->currentId(), $this->scenario->personIdOf($contact), $this->application->now()));
    }

    #[When('j\'en retire :contact')]
    public function iRemoveTheRecipient(string $contact): void
    {
        $this->application->dispatch(new RemoveRecipient($this->application->signedIn(), $this->currentId(), $this->scenario->personIdOf($contact), $this->application->now()));
    }

    #[When('je supprime la campagne :name')]
    public function iDiscardTheCampaign(string $name): void
    {
        $this->current = $name;
        $this->iDiscardIt();
    }

    #[Then('elle est figée')]
    public function itIsFrozen(): void
    {
        Expect::same('frozen', $this->sheet()->status);
    }

    #[Then('ses destinataires sont :contact, à traiter')]
    public function itsRecipientsArePending(string $contact): void
    {
        Expect::same([$this->scenario->personIdOf($contact) => 'à traiter'], array_map(static fn (string $status): string => self::RECIPIENT_STATUSES[$status], $this->sheet()->recipients));
    }

    #[Then('ses destinataires sont :contact')]
    #[Then('ses destinataires sont toujours :contact')]
    public function itsRecipientsAre(string $contact): void
    {
        Expect::same([$this->scenario->personIdOf($contact)], array_keys($this->sheet()->recipients));
    }

    #[Then('ses destinataires sont :one et :other')]
    public function itsRecipientsAreBoth(string $one, string $other): void
    {
        Expect::same([$this->scenario->personIdOf($one), $this->scenario->personIdOf($other)], array_keys($this->sheet()->recipients));
    }

    #[Then(':contact est un destinataire')]
    public function isARecipient(string $contact): void
    {
        Expect::that(array_key_exists($this->scenario->personIdOf($contact), $this->sheet()->recipients), sprintf('%s is no recipient', $contact));
    }

    #[When('je lis le message pour :contact')]
    public function iReadTheMessageFor(string $contact): void
    {
        $this->rendered = $this->application->renderCampaignMessage(new RenderCampaignMessage($this->application->signedIn(), $this->currentId(), $this->scenario->personIdOf($contact)));
    }

    #[When('je liste les campagnes')]
    public function iListTheCampaigns(): void
    {
        [$this->listed, $this->targeted] = $this->application->listCampaigns(new ListCampaigns($this->application->signedIn())) ?? [[], []];
    }

    #[When('je consulte la campagne :name')]
    public function iConsultTheCampaign(string $name): void
    {
        $this->current = $name;
        $this->application->consultCampaign(new ConsultCampaign($this->application->signedIn(), $this->currentId()));
    }

    #[Then('la campagne :name est en brouillon')]
    public function theCampaignIsADraft(string $name): void
    {
        $this->current = $name;
        Expect::same('draft', $this->sheet()->status);
    }

    #[Then('elle cible :')]
    public function itTargets(TableNode $table): void
    {
        $targeted = array_map(static fn (PersonCard $contact): array => ['prénom' => (string) $contact->firstName, 'nom' => (string) $contact->lastName], $this->targetedContacts());

        Expect::same($table->getColumnsHash(), $targeted);
    }

    #[Then('la campagne :name cible :one et :other')]
    public function theCampaignTargetsBoth(string $name, string $one, string $other): void
    {
        $this->current = $name;
        $targeted = array_map(static fn (PersonCard $contact): string => trim(sprintf('%s %s', $contact->firstName, $contact->lastName)), $this->targetedContacts());
        sort($targeted);

        Expect::same([$one, $other], $targeted);
    }

    #[Then('/^"(?<contact>[^"]+)", sans [^,]+, n\'est pas ciblée$/')]
    public function isNotTargeted(string $contact): void
    {
        $absent = $this->scenario->personIdOf($contact);
        Expect::that(!array_any($this->targetedContacts(), static fn (PersonCard $targeted): bool => $targeted->id === $absent), sprintf('%s is targeted', $contact));
    }

    #[Then('ses tags sont :tags')]
    public function itsTagsAre(string $tags): void
    {
        Expect::same($tags, implode(' ', $this->sheet()->targetedTags));
    }

    #[Then('ses tags comprennent :tag')]
    public function itsTagsInclude(string $tag): void
    {
        Expect::that(in_array($tag, $this->sheet()->targetedTags, true), sprintf('%s is not among %s', $tag, implode(' ', $this->sheet()->targetedTags)));
    }

    #[Then('la campagne :name est enregistrée ainsi')]
    public function theCampaignIsSavedSo(string $name): void
    {
        $this->current = $name;
        $sheet = $this->sheet();
        $revision = $this->revision ?? throw new \LogicException('No revision in the scenario');

        Expect::same(
            [$revision->name, $revision->channel, $revision->tags, $revision->message],
            [$sheet->name, $sheet->channel, $sheet->tags, $sheet->message],
        );
    }

    #[Then('il vaut :text')]
    public function itReads(string $text): void
    {
        Expect::same($text, $this->renderedMessage()->text);
    }

    #[Then('son objet vaut :subject')]
    public function itsSubjectReads(string $subject): void
    {
        Expect::same($subject, $this->renderedMessage()->subject);
    }

    #[Then('je n\'ai plus de campagne')]
    public function iHaveNoCampaignAnyMore(): void
    {
        $this->iListTheCampaigns();
        Expect::same([], $this->listed);
    }

    #[Then('j\'y vois :name, préparée par lui')]
    public function iSeeItPreparedByThem(string $name): void
    {
        $listed = array_find($this->listed, static fn (CampaignSheet $campaign): bool => $campaign->name === $name);
        Expect::that(null !== $listed, sprintf('%s is not listed', $name));
        Expect::same($this->scenario->memberIdOf('un autre prospecteur')->value, $listed?->authorId);
    }

    #[Then('les campagnes listées sont :')]
    public function theListedCampaignsAre(TableNode $table): void
    {
        $listed = array_map(fn (CampaignSheet $campaign): array => [
            'nom' => $campaign->name,
            'canal' => (string) array_search($campaign->channel, self::CHANNELS, true),
            'statut' => self::STATUSES[$campaign->status],
            'contactés' => (string) $campaign->contacted,
            'ciblés' => (string) ($this->targeted[$campaign->id] ?? 0),
        ], $this->listed);

        Expect::same($table->getColumnsHash(), $listed);
    }

    private function prepare(string $name, string $channel, string $tags, string $message, ?string $subject = null): void
    {
        $this->current = $name;
        $this->campaignIds[$name] = $this->application->newIdentity();
        $this->application->dispatch(new PrepareCampaign(
            $this->application->signedIn(),
            $this->campaignIds[$name],
            $name,
            self::CHANNELS[$channel] ?? $channel,
            self::listed($tags),
            $message,
            $subject,
            $this->application->now(),
        ));
    }

    private function arrange(string $name, string $channel, string $tags, string $message, ?string $subject = null, ?AccountId $accountId = null, ?MemberId $author = null): void
    {
        $this->current = $name;
        $this->campaignIds[$name] = $this->application->newIdentity();
        $channel = Channel::named(self::CHANNELS[$channel]);
        $this->application->campaigns->save(Campaign::prepare(
            new CampaignId($this->campaignIds[$name]),
            $accountId ?? $this->scenario->me()->accountId(),
            CampaignName::of($name),
            Targeting::by($channel, self::listed($tags)),
            MessageTemplate::of($message, $subject, $channel),
            by: $author ?? new MemberId($this->application->signedIn()),
            at: $this->application->now(),
        ));
    }

    /**
     * As the screen of a draft composes it: its targeted tags searched as every search by tags, the contacts read
     * by PersonalData.
     *
     * @return list<PersonCard>
     */
    private function targetedContacts(): array
    {
        $page = $this->application->searchContactsByTags(new SearchContactsByTags($this->application->signedIn(), $this->sheet()->targetedTags)) ?? throw new \LogicException(sprintf('The search was refused: %s', $this->application->refusal()?->getMessage()));
        $read = $this->application->consultPersons(new ConsultPersons($this->application->signedIn(), $page->contactIds)) ?? [];

        return array_values(array_filter(array_map(static fn (string $id): ?PersonCard => $read[$id] ?? null, $page->contactIds)));
    }

    private function sheet(): CampaignSheet
    {
        return $this->application->consultCampaign(new ConsultCampaign($this->application->signedIn(), $this->currentId()))->campaign
            ?? throw new \LogicException(sprintf('The campaign was refused: %s', $this->application->refusal()?->getMessage()));
    }

    private function renderedMessage(): CampaignMessage
    {
        return $this->rendered ?? throw new \LogicException(sprintf('The message was refused: %s', $this->application->refusal()?->getMessage()));
    }

    private function currentName(): string
    {
        return $this->current ?? throw new \LogicException('No campaign in the scenario');
    }

    private function currentId(): string
    {
        return $this->campaignIds[$this->currentName()] ?? throw new \LogicException(sprintf('No campaign "%s" in the scenario', $this->currentName()));
    }

    /**
     * @return list<string> the tags of `#ESN #STRASBOURG`, none for nothing
     */
    private static function listed(string $tags): array
    {
        return array_values(array_filter(explode(' ', $tags), static fn (string $tag): bool => '' !== $tag));
    }
}
