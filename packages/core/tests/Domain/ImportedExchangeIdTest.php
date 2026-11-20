<?php

declare(strict_types=1);

namespace Tests\Core\Domain;

use PHPUnit\Framework\TestCase;
use Prospecter\Core\Prospecting\Domain\Model\ExchangeId;
use Prospecter\Core\SharedKernel\Domain\AccountId;
use Prospecter\Core\SharedKernel\Domain\OrganizationId;
use Prospecter\Core\SharedKernel\Domain\PersonId;

class ImportedExchangeIdTest extends TestCase
{
    private const string ACCOUNT = '0190a1c0-0000-7000-8000-000000000001';
    private const string OTHER_ACCOUNT = '0190a1c0-0000-7000-8000-000000000002';
    private const string ADA = '0190a1c0-0000-7000-8000-00000000000a';
    private const string GLOBEX = '0190a1c0-0000-7000-8000-00000000000b';
    private const string EMAIL = 'f00d';

    public function testTheExchangeOfAnEMailImportedTwiceIsTheSame(): void
    {
        $this->assertTrue(self::of(self::ACCOUNT, self::EMAIL, new PersonId(self::ADA))->equals(self::of(self::ACCOUNT, self::EMAIL, new PersonId(self::ADA))));
    }

    public function testTheExchangeOfAnotherEMailIsAnother(): void
    {
        $this->assertFalse(self::of(self::ACCOUNT, self::EMAIL, new PersonId(self::ADA))->equals(self::of(self::ACCOUNT, 'beef', new PersonId(self::ADA))));
    }

    public function testTheExchangeOfAnEMailWithAnotherCorrespondentIsAnother(): void
    {
        $this->assertFalse(self::of(self::ACCOUNT, self::EMAIL, new PersonId(self::ADA))->equals(self::of(self::ACCOUNT, self::EMAIL, new OrganizationId(self::GLOBEX))));
    }

    public function testTheExchangeOfAnEMailInAnotherAccountIsAnother(): void
    {
        $this->assertFalse(self::of(self::ACCOUNT, self::EMAIL, new PersonId(self::ADA))->equals(self::of(self::OTHER_ACCOUNT, self::EMAIL, new PersonId(self::ADA))));
    }

    private static function of(string $account, string $email, PersonId|OrganizationId $correspondent): ExchangeId
    {
        return ExchangeId::ofImportedEmail(new AccountId($account), $email, $correspondent);
    }
}
