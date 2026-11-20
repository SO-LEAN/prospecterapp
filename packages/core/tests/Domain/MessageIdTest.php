<?php

declare(strict_types=1);

namespace Tests\Core\Domain;

use PHPUnit\Framework\TestCase;
use Prospecter\Core\SharedKernel\Domain\MessageId;

class MessageIdTest extends TestCase
{
    public function testTheSameEMailIsIdentifiedWithoutItsChevronsNorItsCase(): void
    {
        $this->assertSame(new MessageId('abc@acme.fr')->identity(), new MessageId(' <ABC@Acme.fr> ')->identity());
    }

    public function testWhatTheEMailSaysStaysOutOfItsIdentity(): void
    {
        $this->assertSame(hash('sha256', 'abc@acme.fr'), new MessageId('<abc@acme.fr>')->identity());
    }

    public function testAnEMailWithoutMessageIdHasNone(): void
    {
        $this->assertTrue(new MessageId(' <> ')->isMissing());
    }
}
