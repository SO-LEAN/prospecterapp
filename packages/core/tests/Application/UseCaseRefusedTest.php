<?php

declare(strict_types=1);

namespace Tests\Core\Application;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Prospecter\Core\Prospecting\Domain\Exception\UnknownOrganization;
use Prospecter\Core\SharedKernel\Application\Exception\ErrorType;
use Prospecter\Core\SharedKernel\Application\Exception\UseCaseRefused;
use Prospecter\Core\SharedKernel\Domain\Exception\BusinessException;
use Prospecter\Core\SharedKernel\Domain\Exception\BusinessRuleViolation;
use Prospecter\Core\SharedKernel\Domain\Exception\EmailAlreadyUsed;
use Prospecter\Core\SharedKernel\Domain\Exception\NotAllowed;
use Prospecter\Core\SharedKernel\Domain\Exception\NotPossibleInItsState;
use Prospecter\Core\SharedKernel\Domain\OrganizationId;

class UseCaseRefusedTest extends TestCase
{
    #[DataProvider('refusals')]
    public function testARefusalOfTheDomainIsTypedWordedAndKept(BusinessException $refusal, ErrorType $type, string $field): void
    {
        $translated = UseCaseRefused::because($refusal);

        $this->assertSame($type, $translated->type);
        $this->assertSame($refusal->getMessage(), $translated->getMessage());
        $this->assertSame($field, $translated->field);
        $this->assertSame($refusal, $translated->getPrevious());
    }

    /**
     * @return iterable<string, array{BusinessException, ErrorType, string}>
     */
    public static function refusals(): iterable
    {
        yield 'unknown' => [UnknownOrganization::identifiedBy(new OrganizationId('0190a1c0-0000-7000-8000-000000000001')), ErrorType::NotFound, UseCaseRefused::WHOLE_REQUEST];
        yield 'no right' => [NotAllowed::because('Only the person may change it'), ErrorType::NotAllowed, UseCaseRefused::WHOLE_REQUEST];
        yield 'not in its state' => [NotPossibleInItsState::because('There is no logo to remove', 'logo'), ErrorType::NotPossibleInItsState, 'logo'];
        yield 'a rule on a field' => [EmailAlreadyUsed::byAnother(), ErrorType::BusinessRuleViolated, 'email'];
        yield 'a rule on the whole' => [BusinessRuleViolation::because('At least one is mandatory : corporate name or email'), ErrorType::BusinessRuleViolated, UseCaseRefused::WHOLE_REQUEST];
    }
}
