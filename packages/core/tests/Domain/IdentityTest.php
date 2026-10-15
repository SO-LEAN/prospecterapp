<?php

declare(strict_types=1);

namespace Tests\Core\Domain;

use PHPUnit\Framework\TestCase;
use Prospecter\Core\Domain\Organization\OrganizationId;
use Prospecter\Core\Domain\User\UserId;

class IdentityTest extends TestCase
{
    private const string UUID = '0190a1c0-0000-7000-8000-000000000001';

    public function testAnIdentityIsAUuid(): void
    {
        $this->assertSame(self::UUID, (string) new OrganizationId(self::UUID));
    }

    public function testAnythingElseIsRefused(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        new OrganizationId('42');
    }

    public function testTwoIdentitiesOfTheSameKindAndValueAreEqual(): void
    {
        $this->assertTrue(new OrganizationId(self::UUID)->equals(new OrganizationId(self::UUID)));
        $this->assertFalse(new OrganizationId(self::UUID)->equals(new UserId(self::UUID)), 'an organization is not a user');
    }
}
