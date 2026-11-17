<?php

declare(strict_types=1);

namespace Tests\Core\Acceptance\Context;

use Behat\Behat\Context\Context;
use Behat\Gherkin\Node\TableNode;
use Behat\Step\Given;
use Behat\Step\Then;
use Behat\Step\When;
use Prospecter\Core\Campaigns\Application\ConsultSuggestedTags\ConsultSuggestedTags;
use Prospecter\Core\Campaigns\Application\ConsultTags\ConsultTags;
use Prospecter\Core\Campaigns\Application\SuggestedTags;
use Prospecter\Core\Campaigns\Application\Tag\Tag as TagCommand;
use Prospecter\Core\Campaigns\Application\Untag\Untag;
use Prospecter\Core\Campaigns\Domain\Model\Holder;
use Prospecter\Core\Campaigns\Domain\Model\Tag;
use Prospecter\Core\Campaigns\Domain\Model\Tagging;
use Prospecter\Core\Prospecting\Domain\Model\Organization;
use Prospecter\Core\SharedKernel\Domain\AccountId;
use Prospecter\Core\SharedKernel\Domain\ContactDetails;
use Prospecter\Core\SharedKernel\Domain\LegalIdentity;
use Prospecter\Core\SharedKernel\Domain\MemberId;
use Prospecter\Core\SharedKernel\Domain\OrganizationId;
use Prospecter\Core\SharedKernel\Domain\PersonId;
use Tests\Core\Acceptance\Expect;

/**
 * The tags the members put by hand on their organizations and their contacts, and those proposed to their account
 * (Campaigns, ADR 0138).
 */
final class TagContext implements Context
{
    use SharesTheScenario;

    private const array FAMILIES = [
        'type d\'organisation' => 'organizationType',
        'relation avec le contact' => 'relationship',
        'technos' => 'technology',
    ];
    private const string MINE = 'mes tags';

    #[When('/^je tague "(?<holder>[^"]+)" avec (?<tags>"[^"]*"(?: et "[^"]*")*)$/')]
    public function iTag(string $holder, string $tags): void
    {
        [$kind, $id] = $this->holderNamed($holder);
        $this->application->dispatch(new TagCommand($this->application->signedIn(), $kind, $id, self::listed($tags), $this->application->now()));
    }

    #[When('je retire le tag :tag de :holder')]
    public function iUntag(string $tag, string $holder): void
    {
        [$kind, $id] = $this->holderNamed($holder);
        $this->application->dispatch(new Untag($this->application->signedIn(), $kind, $id, $tag, $this->application->now()));
    }

    #[Given(':holder est tagué :tag')]
    public function isTagged(string $holder, string $tag): void
    {
        $this->tag($this->scenario->me()->accountId(), $this->holderOf($holder), $tag);
    }

    #[Given('un prospecteur de :account a créé le tag :tag')]
    public function aProspectorOfCreatedTheTag(string $account, string $tag): void
    {
        $accountId = $this->scenario->accountIdOf($account);
        $organization = Organization::prospectedBy($this->scenario->prospectorOf($accountId), new OrganizationId($this->application->newIdentity()), new LegalIdentity('Organisation de '.$account), new ContactDetails(), $this->application->now());
        $this->application->organizations->save($organization);
        $this->tag($accountId, Holder::organization($organization->id()), $tag);
    }

    #[Then('les tags de :holder sont :tags')]
    public function theTagsAre(string $holder, string $tags): void
    {
        Expect::same($tags, implode(' ', $this->tagsOf($holder)));
    }

    #[Then(':tag fait partie des tags proposés de mon compte')]
    public function isProposed(string $tag): void
    {
        Expect::that(in_array($tag, $this->proposed(), true), sprintf('%s is not proposed', $tag));
    }

    #[Then(':tag ne fait plus partie des tags proposés de mon compte')]
    #[Then(':tag ne fait pas partie des tags proposés de mon compte')]
    public function isNotProposed(string $tag): void
    {
        Expect::that(!in_array($tag, $this->proposed(), true), sprintf('%s is proposed', $tag));
    }

    #[Then('les tags proposés de mon compte sont :')]
    public function theProposedTagsAre(TableNode $table): void
    {
        $suggested = $this->suggested();
        $read = [];

        foreach ($table->getColumnsHash() as $row) {
            $tags = self::MINE === $row['famille'] ? $suggested->own : ($suggested->predefined[self::FAMILIES[$row['famille']]] ?? []);
            $read[] = ['famille' => $row['famille'], 'tags' => implode(' ', $tags)];
        }

        Expect::same(array_map(static fn (array $row): array => ['famille' => $row['famille'], 'tags' => trim($row['tags'])], $table->getColumnsHash()), $read);
        Expect::same(array_values(self::FAMILIES), array_keys($suggested->predefined));
    }

    #[Then('les tags proposés de mon compte sont, parmi les miens, :tags')]
    public function myOwnProposedTagsAre(string $tags): void
    {
        Expect::same($tags, implode(' ', $this->suggested()->own));
    }

    #[Then('aucun tag n\'est gardé pour :holder')]
    public function noTagIsKeptFor(string $holder): void
    {
        $holder = $this->holderOf($holder);
        Expect::that(null === $this->application->taggings->find($this->scenario->me()->accountId(), $holder), sprintf('Tags are kept for %s', $holder->id));
    }

    #[Then('aucun tag n\'est gardé pour :holder ni pour :other')]
    public function noTagIsKeptForEither(string $holder, string $other): void
    {
        $this->noTagIsKeptFor($holder);
        $this->noTagIsKeptFor($other);
    }

    private function tag(AccountId $accountId, Holder $holder, string $tag): void
    {
        $tagging = $this->application->taggings->find($accountId, $holder) ?? Tagging::of($accountId, $holder, $this->application->now());
        $tagging->tagWith([Tag::written($tag)], by: new MemberId($this->application->newIdentity()), at: $this->application->now());
        $this->application->taggings->save($tagging);
    }

    /**
     * @return list<string>
     */
    private function tagsOf(string $holder): array
    {
        [$kind, $id] = $this->holderNamed($holder);

        return $this->application->consultTags(new ConsultTags($this->application->signedIn(), $kind, $id)) ?? [];
    }

    /**
     * @return list<string>
     */
    private function proposed(): array
    {
        $suggested = $this->suggested();

        return [...array_merge(...array_values($suggested->predefined)), ...$suggested->own];
    }

    private function suggested(): SuggestedTags
    {
        return $this->application->consultSuggestedTags(new ConsultSuggestedTags($this->application->signedIn())) ?? throw new \LogicException(sprintf('The tags proposed were refused: %s', $this->application->refusal()?->getMessage()));
    }

    /**
     * @return array{string, string} what a request names: the kind of what it tags, and its identity
     */
    private function holderNamed(string $name): array
    {
        $holder = $this->holderOf($name);

        return [$holder->kind->value, $holder->id->value];
    }

    private function holderOf(string $name): Holder
    {
        return $this->scenario->isAnOrganization($name)
            ? Holder::organization(new OrganizationId($this->scenario->idOf($name)))
            : Holder::contact(new PersonId($this->scenario->personIdOf($name)));
    }

    /**
     * @return list<string> the tags of `"#ESN" et "#APPORTEUR"`
     */
    private static function listed(string $tags): array
    {
        preg_match_all('/"([^"]*)"/', $tags, $matches);

        return $matches[1];
    }
}
