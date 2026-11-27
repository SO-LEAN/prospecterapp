<?php

declare(strict_types=1);

namespace Tests\Core\Domain;

use PHPUnit\Framework\TestCase;
use Prospecter\Core\Campaigns\Domain\Model\AddresseeOrganization;

/**
 * The organization a message names among those a contact works for: the one they were attached to most recently.
 */
final class AddresseeOrganizationTest extends TestCase
{
    private const string EARLIER = '2026-11-24 09:00';
    private const string LATER = '2026-11-25 09:00';

    public function testTheMostRecentAttachmentIsShown(): void
    {
        $shown = AddresseeOrganization::shownAmong([self::attached('ACME', self::EARLIER), self::attached('Globex', self::LATER), self::attached('Initech', self::EARLIER)]);

        $this->assertSame('Globex', $shown?->name);
    }

    public function testAnAttachmentWithoutItsMomentIsOlderThanAnyOther(): void
    {
        $shown = AddresseeOrganization::shownAmong([new AddresseeOrganization('ACME', null), self::attached('Globex', self::EARLIER)]);

        $this->assertSame('Globex', $shown?->name);
    }

    public function testAmongAttachmentsWithoutTheirMomentTheFirstIsShown(): void
    {
        $shown = AddresseeOrganization::shownAmong([new AddresseeOrganization('ACME', null), new AddresseeOrganization('Globex', null)]);

        $this->assertSame('ACME', $shown?->name);
    }

    public function testAmongAttachmentsOfTheSameMomentTheFirstIsShown(): void
    {
        $shown = AddresseeOrganization::shownAmong([self::attached('ACME', self::LATER), self::attached('Globex', self::LATER)]);

        $this->assertSame('ACME', $shown?->name);
    }

    public function testAContactOfNoOrganizationShowsNone(): void
    {
        $this->assertNull(AddresseeOrganization::shownAmong([]));
    }

    private static function attached(string $name, string $at): AddresseeOrganization
    {
        return new AddresseeOrganization($name, new \DateTimeImmutable($at));
    }
}
