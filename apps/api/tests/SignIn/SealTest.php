<?php

namespace Tests\App\SignIn;

use App\SignIn\OAuth\BrokenSeal;
use App\SignIn\OAuth\Seal;
use App\SignIn\OAuth\SealPurpose;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Clock\MockClock;

/**
 * The facade signing the AIs in keeps nothing (ADR 0157): what it must find again travels sealed, readable by the API
 * alone, for a while.
 */
class SealTest extends TestCase
{
    private MockClock $clock;

    protected function setUp(): void
    {
        $this->clock = new MockClock('2026-10-15 09:00 UTC');
    }

    public function testWhatIsSealedIsFoundAgainWhenOpened(): void
    {
        $seal = $this->seal();

        $sealed = $seal->close(['client_id' => 'https://claude.ai/oauth/claude-code-client-metadata'], SealPurpose::SignIn);

        $this->assertSame(['client_id' => 'https://claude.ai/oauth/claude-code-client-metadata'], $seal->open($sealed, SealPurpose::SignIn));
    }

    public function testASealSomeoneChangedDoesNotOpen(): void
    {
        $seal = $this->seal();
        $sealed = $seal->close(['client_id' => 'https://claude.ai/oauth/claude-code-client-metadata'], SealPurpose::SignIn);

        $this->expectException(BrokenSeal::class);

        $seal->open(strrev($sealed), SealPurpose::SignIn);
    }

    public function testWhatIsNotASealDoesNotOpen(): void
    {
        $this->expectException(BrokenSeal::class);

        $this->seal()->open('not a seal!', SealPurpose::SignIn);
    }

    public function testASealCutShortDoesNotOpen(): void
    {
        $this->expectException(BrokenSeal::class);

        $this->seal()->open('c2hvcnQ', SealPurpose::SignIn);
    }

    public function testASealOpensOnlyForWhatItWasClosedFor(): void
    {
        $seal = $this->seal();
        $sealed = $seal->close(['client_id' => 'https://claude.ai/oauth/claude-code-client-metadata'], SealPurpose::SignIn);

        $this->expectException(BrokenSeal::class);

        $seal->open($sealed, SealPurpose::AuthorizationCode);
    }

    public function testAnAuthorizationCodeOpensWithinFiveMinutes(): void
    {
        $seal = $this->seal();
        $sealed = $seal->close(['code' => 'the code of Cognito'], SealPurpose::AuthorizationCode);
        $this->clock->modify('+5 minutes');

        $this->assertSame(['code' => 'the code of Cognito'], $seal->open($sealed, SealPurpose::AuthorizationCode));
    }

    public function testAnAuthorizationCodeNoLongerOpensAfterFiveMinutes(): void
    {
        $seal = $this->seal();
        $sealed = $seal->close(['code' => 'the code of Cognito'], SealPurpose::AuthorizationCode);
        $this->clock->modify('+5 minutes +1 second');

        $this->expectException(BrokenSeal::class);

        $seal->open($sealed, SealPurpose::AuthorizationCode);
    }

    private function seal(): Seal
    {
        return new Seal(str_repeat('k', SODIUM_CRYPTO_SECRETBOX_KEYBYTES), $this->clock);
    }
}
