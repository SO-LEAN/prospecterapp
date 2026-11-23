<?php

declare(strict_types=1);

namespace Tests\Core\Domain;

use PHPUnit\Framework\TestCase;
use Prospecter\Core\Campaigns\Domain\Event\ContactTagsChanged;
use Prospecter\Core\Campaigns\Domain\Event\OrganizationTagsChanged;
use Prospecter\Core\Campaigns\Domain\Exception\AutomaticTag;
use Prospecter\Core\Campaigns\Domain\Exception\TagNotCarried;
use Prospecter\Core\Campaigns\Domain\Model\ContactId;
use Prospecter\Core\Campaigns\Domain\Model\Holder;
use Prospecter\Core\Campaigns\Domain\Model\OrganizationId;
use Prospecter\Core\Campaigns\Domain\Model\Tag;
use Prospecter\Core\Campaigns\Domain\Model\Tagging;
use Prospecter\Core\SharedKernel\Domain\Identity\AccountId;
use Prospecter\Core\SharedKernel\Domain\Identity\MemberId;

/**
 * Each change to an organization's or a contact's tags records what happened. An automatic tag is never put by hand,
 * and changes nothing.
 */
final class TaggingTest extends TestCase
{
    private const string ACCOUNT = '0190a1c0-0000-7000-8000-000000000001';
    private const string MEMBER = '0190a1c0-0000-7000-8000-0000000000aa';
    private const string ORGANIZATION = '0190a1c0-0000-7000-8000-0000000000b1';
    private const string CONTACT = '0190a1c0-0000-7000-8000-0000000000c1';
    private const string AT = '2026-11-17 10:00';

    public function testTaggingAnOrganizationRecordsIt(): void
    {
        $tagging = $this->taggingOf(Holder::organization(new OrganizationId(self::ORGANIZATION)));

        $tagging->tagWith([Tag::written('#ESN')], new MemberId(self::MEMBER), new \DateTimeImmutable(self::AT));

        $this->assertEquals([new OrganizationTagsChanged(new AccountId(self::ACCOUNT), new MemberId(self::MEMBER), new OrganizationId(self::ORGANIZATION), new \DateTimeImmutable(self::AT))], $tagging->releaseEvents());
    }

    public function testTakingATagOffAContactRecordsIt(): void
    {
        $tagging = $this->taggingOf(Holder::contact(new ContactId(self::CONTACT)));
        $tagging->tagWith([Tag::written('#CLIENT')], new MemberId(self::MEMBER), new \DateTimeImmutable(self::AT));
        $tagging->releaseEvents();

        $tagging->takeOff(Tag::written('#CLIENT'), new MemberId(self::MEMBER), new \DateTimeImmutable(self::AT));

        $this->assertEquals([new ContactTagsChanged(new AccountId(self::ACCOUNT), new MemberId(self::MEMBER), new ContactId(self::CONTACT), new \DateTimeImmutable(self::AT))], $tagging->releaseEvents());
    }

    public function testAnAutomaticTagAmongOthersChangesNothing(): void
    {
        $tagging = $this->taggingOf(Holder::contact(new ContactId(self::CONTACT)));

        try {
            $tagging->tagWith([Tag::written('#CLIENT'), Tag::written('#EMAIL')], new MemberId(self::MEMBER), new \DateTimeImmutable(self::AT));
            $this->fail('An automatic tag was put by hand');
        } catch (AutomaticTag) {
            $this->assertSame([], $tagging->releaseEvents());
            $this->expectException(TagNotCarried::class);
            $tagging->takeOff(Tag::written('#CLIENT'), new MemberId(self::MEMBER), new \DateTimeImmutable(self::AT));
        }
    }

    private function taggingOf(Holder $holder): Tagging
    {
        return Tagging::of(new AccountId(self::ACCOUNT), $holder, new \DateTimeImmutable(self::AT));
    }
}
