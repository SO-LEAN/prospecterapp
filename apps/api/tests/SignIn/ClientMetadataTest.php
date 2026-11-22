<?php

declare(strict_types=1);

namespace Tests\App\SignIn;

use App\SignIn\OAuth\ClientMetadata;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * An AI identifies itself by its Client ID Metadata Document, declaring where it may be sent back (ADR 0157).
 */
class ClientMetadataTest extends TestCase
{
    private const string CLAUDE_WEB = 'https://claude.ai/api/mcp/auth_callback';

    public function testAClientIsSentBackWhereItDeclares(): void
    {
        $this->assertTrue(new ClientMetadata([self::CLAUDE_WEB])->declares(self::CLAUDE_WEB));
    }

    public function testAClientIsNotSentBackWhereItDoesNotDeclare(): void
    {
        $this->assertFalse(new ClientMetadata([self::CLAUDE_WEB])->declares('https://claude.ai.evil.example/api/mcp/auth_callback'));
    }

    #[DataProvider('loopbackAddresses')]
    public function testAClientOnTheLoopbackIsSentBackWhateverItsPort(string $declared, string $given): void
    {
        $this->assertTrue(new ClientMetadata([$declared])->declares($given));
    }

    public function testOnTheLoopbackAllButThePortIsMatched(): void
    {
        $this->assertFalse(new ClientMetadata(['http://localhost/callback'])->declares('http://localhost:53124/callback?next=https://evil.example'));
    }

    /**
     * @return iterable<string, array{string, string}>
     */
    public static function loopbackAddresses(): iterable
    {
        yield 'localhost' => ['http://localhost/callback', 'http://localhost:53124/callback'];
        yield 'IPv4' => ['http://127.0.0.1/callback', 'http://127.0.0.1:8123/callback'];
        yield 'IPv6' => ['http://[::1]/callback', 'http://[::1]:8123/callback'];
    }
}
