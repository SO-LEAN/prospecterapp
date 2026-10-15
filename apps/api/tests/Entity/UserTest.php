<?php

namespace Tests\App\Entity;

use App\Entity\User;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;
use Prospecter\Core\Entity\Organization;
use Prospecter\Core\Entity\User as CoreUser;

#[Group('unit')]
class UserTest extends TestCase
{
    public function testTheSignedInUserIsTheUseCaseConsumer(): void
    {
        $organization = new Organization();
        $organization->setId(777);
        $user = new CoreUser();
        $user->setId(123);
        $user->setUserName('ada');
        $user->setRoles(['ROLE_USER', 'ROLE_PROSPECTOR']);
        $user->setOrganization($organization);

        $signedIn = User::from($user);

        $this->assertSame(123, $signedIn->getUserId());
        $this->assertSame(777, $signedIn->getOrganizationId());
        $this->assertSame('ada', $signedIn->getUserIdentifier());
        $this->assertSame(['ROLE_USER', 'ROLE_PROSPECTOR'], $signedIn->getRoles());
    }
}
