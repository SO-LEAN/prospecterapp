<?php

declare(strict_types=1);

namespace Tests\Core\Application;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Prospecter\Core\Prospecting\Domain\Exception\NoLogoToRemove;
use Prospecter\Core\Prospecting\Domain\Exception\UnknownOrganization;
use Prospecter\Core\SharedKernel\Application\Exception\ErrorType;
use Prospecter\Core\SharedKernel\Application\Exception\UseCaseRefused;
use Prospecter\Core\SharedKernel\Domain\Exception\BusinessException;
use Prospecter\Core\SharedKernel\Domain\Exception\BusinessRuleViolation;
use Prospecter\Core\SharedKernel\Domain\Exception\EmailAlreadyUsed;
use Prospecter\Core\SharedKernel\Domain\Exception\InvalidPhoneNumber;
use Prospecter\Core\SharedKernel\Domain\Exception\NotPossibleInItsState;
use Prospecter\Core\SharedKernel\Domain\Exception\PersonalDataManagersOnly;
use Prospecter\Core\SharedKernel\Domain\OrganizationId;

class UseCaseRefusedTest extends TestCase
{
    #[DataProvider('refusals')]
    public function testARefusalOfTheDomainIsTypedWordedAndKept(BusinessException $refusal, ErrorType $type, string $field, string $reason): void
    {
        $translated = UseCaseRefused::because($refusal);

        $this->assertSame($type, $translated->type);
        $this->assertSame($reason, $translated->reason);
        $this->assertSame($refusal->getMessage(), $translated->getMessage());
        $this->assertSame($field, $translated->field);
        $this->assertSame($refusal, $translated->getPrevious());
    }

    /**
     * @return iterable<string, array{BusinessException, ErrorType, string, string}>
     */
    public static function refusals(): iterable
    {
        yield 'unknown' => [UnknownOrganization::identifiedBy(new OrganizationId('0190a1c0-0000-7000-8000-000000000001')), ErrorType::NotFound, UseCaseRefused::WHOLE_REQUEST, 'unknownOrganization'];
        yield 'no right' => [PersonalDataManagersOnly::because('Only a manager may collect a contact'), ErrorType::NotAllowed, UseCaseRefused::WHOLE_REQUEST, 'personalDataManagersOnly'];
        yield 'not in its state' => [NoLogoToRemove::because('There is no logo to remove', 'logo'), ErrorType::NotPossibleInItsState, 'logo', 'noLogoToRemove'];
        yield 'not in its state, without a refusal of its own' => [NotPossibleInItsState::because('Relocate is not possible now'), ErrorType::NotPossibleInItsState, UseCaseRefused::WHOLE_REQUEST, 'notPossibleInItsState'];
        yield 'a rule on a field' => [EmailAlreadyUsed::byAnother(), ErrorType::BusinessRuleViolated, 'email', 'emailAlreadyUsed'];
        yield 'a rule on the whole' => [BusinessRuleViolation::because('At least one is mandatory : corporate name or email'), ErrorType::BusinessRuleViolated, UseCaseRefused::WHOLE_REQUEST, 'businessRuleViolation'];
    }

    public function testARefusalKeepsWhatItIsAbout(): void
    {
        $this->assertSame(['phoneNumber' => '03554411'], UseCaseRefused::because(InvalidPhoneNumber::of('03554411'))->context);
    }
}
