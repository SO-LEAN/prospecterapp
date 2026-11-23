<?php

declare(strict_types=1);

namespace Tests\Core\Architecture;

use PHPUnit\Framework\TestCase;
use Prospecter\Core\SharedKernel\Domain\Identity\AccountId;
use Prospecter\Core\SharedKernel\Domain\Identity\MemberId;
use Prospecter\Core\SharedKernel\Domain\Identity\Uuid;

/**
 * A context has its own identities for the things it refers to in another context. Only the account and the member who acts
 * mean the same in every context, so only their identities stay in the shared kernel (ADR 0197).
 */
class SharedIdentitiesTest extends TestCase
{
    private const array SHARED = [AccountId::class, MemberId::class];

    public function testOnlyTheAccountAndTheMemberHaveTheirIdentityInTheSharedKernel(): void
    {
        $this->assertSame([], array_values(array_diff($this->identitiesOfTheSharedKernel(), self::SHARED)));
    }

    /**
     * @return list<string>
     */
    private function identitiesOfTheSharedKernel(): array
    {
        $root = dirname(__DIR__, 2).'/src/';
        $identities = [];

        foreach (FilesUnder::the($root.'SharedKernel') as $path) {
            $class = 'Prospecter\Core\\'.str_replace(['/', '.php'], ['\\', ''], substr($path, strlen($root)));

            if (is_subclass_of($class, Uuid::class)) {
                $identities[] = $class;
            }
        }

        return $identities;
    }
}
