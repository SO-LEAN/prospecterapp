<?php

namespace Tests\App\Entity;

use App\Entity\User;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;
use Prospecter\Core\Domain\Organization\OrganizationId;
use Prospecter\Core\Domain\User\Role;
use Prospecter\Core\Domain\User\User as CoreUser;
use Prospecter\Core\Domain\User\UserId;

#[Group('unit')]
class UserTest extends TestCase
{
    public function testTheSignedInUserIsTheUseCaseConsumer(): void
    {
        $userId = new UserId('0190a1c0-0000-7000-8000-000000000001');
        $organizationId = new OrganizationId('0190a1c0-0000-7000-8000-000000000002');

        $signedIn = User::from(CoreUser::register($userId, $organizationId, 'ada', Role::User, Role::Prospector));

        $this->assertEquals($userId, $signedIn->getUserId());
        $this->assertEquals($organizationId, $signedIn->getOrganizationId());
        $this->assertSame('ada', $signedIn->getUserIdentifier());
        $this->assertSame(['ROLE_USER', 'ROLE_PROSPECTOR'], $signedIn->getRoles());
    }
}
