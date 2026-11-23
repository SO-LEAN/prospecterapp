<?php

declare(strict_types=1);

namespace Tests\Core\Acceptance\Context;

use Behat\Behat\Context\Context;
use Behat\Step\Given;
use Behat\Step\Then;
use Behat\Step\When;
use Prospecter\Core\Campaigns\Application\ConsultContactTags\ConsultContactTags;
use Prospecter\Core\Campaigns\Application\ConsultContactTags\ContactTagsResponse;
use Prospecter\Core\Campaigns\Application\ConsultContactTags\InheritedTagResponse;
use Prospecter\Core\Campaigns\Application\RebuildTags\RebuildTags;
use Prospecter\Core\Prospecting\Domain\Model\OrganizationId;
use Tests\Core\Acceptance\Expect;

final class ContactTagsContext implements Context
{
    use SharesTheScenario;

    #[Given('les tags calculés ont été reconstruits')]
    #[When('les tags calculés sont reconstruits')]
    public function theComputedTagsAreRebuilt(): void
    {
        $this->application->dispatch(new RebuildTags(null));
    }

    #[Given('les tags calculés de :account ont été perdus')]
    public function theComputedTagsOfWereLost(string $account): void
    {
        $this->application->campaignsFacts->loseEveryFactOf($this->scenario->accountIdOf($account));
    }

    #[When('je consulte les tags de :name')]
    public function iConsultTheTagsOf(string $name): void
    {
        $this->consult($name);
    }

    #[Then('/^"(?<contact>[^"]+)" porte "(?<tag>[^"]+)", hérité de (?<organizations>"[^"]+"(?: et "[^"]+")*)$/')]
    public function carriesInheritedFrom(string $contact, string $tag, string $organizations): void
    {
        $inherited = array_find($this->tagsOf($contact)->inherited, static fn (InheritedTagResponse $inherited): bool => $inherited->name === $tag);

        $inherited = Expect::present($inherited, sprintf('%s does not inherit %s', $contact, $tag));
        Expect::same(self::listed($organizations), array_map(fn (string $id): string => $this->scenario->nameOf(new OrganizationId($id)), $inherited->from));
    }

    #[Then(':contact porte :tag, le sien')]
    public function carriesAsItsOwn(string $contact, string $tag): void
    {
        $tags = $this->tagsOf($contact);

        Expect::that(in_array($tag, $tags->own, true), sprintf('%s does not carry %s as its own', $contact, $tag));
        Expect::that(!in_array($tag, self::namesOf($tags->inherited), true), sprintf('%s is shown inherited too', $tag));
    }

    #[Then(':contact ne porte plus :tag')]
    public function noLongerCarries(string $contact, string $tag): void
    {
        $tags = $this->tagsOf($contact);

        Expect::that(!in_array($tag, [...$tags->own, ...self::namesOf($tags->inherited), ...$tags->automatic], true), sprintf('%s still carries %s', $contact, $tag));
    }

    #[Then('les tags automatiques de :contact sont :tags')]
    public function theAutomaticTagsAre(string $contact, string $tags): void
    {
        Expect::same($tags, implode(' ', $this->tagsOf($contact)->automatic));
    }

    private function tagsOf(string $contact): ContactTagsResponse
    {
        return $this->consult($contact) ?? throw new \LogicException(sprintf('The tags of %s were refused: %s', $contact, $this->application->refusal()?->getMessage()));
    }

    private function consult(string $contact): ?ContactTagsResponse
    {
        $kept = $this->application->consultContactTags(new ConsultContactTags($this->application->signedIn(), [$this->scenario->personIdOf($contact)]));

        return $kept?->contacts[0] ?? null;
    }

    /**
     * @param list<InheritedTagResponse> $inherited
     *
     * @return list<string>
     */
    private static function namesOf(array $inherited): array
    {
        return array_map(static fn (InheritedTagResponse $tag): string => $tag->name, $inherited);
    }

    /**
     * @return list<string> the names in `"ACME" et "Globex"`
     */
    private static function listed(string $names): array
    {
        preg_match_all('/"([^"]*)"/', $names, $matches);

        return $matches[1];
    }
}
