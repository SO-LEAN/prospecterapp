<?php

declare(strict_types=1);

namespace Tests\Core\Domain;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Prospecter\Core\SharedKernel\Domain\Contact\Email;
use Prospecter\Core\SharedKernel\Domain\Exception\BusinessRuleViolation;

class EmailTest extends TestCase
{
    public function testAValidAddressIsAnEmail(): void
    {
        $this->assertSame('contact@acme.test', (string) new Email('contact@acme.test'));
    }

    #[DataProvider('notEmails')]
    public function testAnInvalidAddressIsRefusedOnTheEmailField(string $notAnEmail): void
    {
        $this->expectExceptionObject(BusinessRuleViolation::because(sprintf('Email "%s" is not valid', $notAnEmail), 'email'));

        new Email($notAnEmail);
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function notEmails(): iterable
    {
        yield 'no at sign' => ['contact.acme.test'];
        yield 'no domain' => ['contact@'];
        yield 'spaces' => ['contact @acme.test'];
    }

    public function testAnAddressIsTheSameWhateverItsCase(): void
    {
        $this->assertTrue(new Email('Michel@Exemple.fr')->equals(new Email('michel@exemple.fr')));
        $this->assertFalse(new Email('michel@exemple.fr')->equals(new Email('autre@exemple.fr')));
    }

    public function testItsDomainIsWhatFollowsTheAt(): void
    {
        $this->assertSame('acme.fr', new Email('"ada@home"@ACME.fr')->domain());
    }

    public function testNoAddressIsNoEmail(): void
    {
        $this->assertNull(Email::orNone(null));
        $this->assertNull(Email::orNone(''));
    }
}
