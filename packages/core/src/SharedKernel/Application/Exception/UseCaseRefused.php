<?php

declare(strict_types=1);

namespace Prospecter\Core\SharedKernel\Application\Exception;

use Prospecter\Core\SharedKernel\Domain\Exception\BusinessException;
use Prospecter\Core\SharedKernel\Domain\Exception\BusinessRuleViolation;
use Prospecter\Core\SharedKernel\Domain\Exception\NotAllowed;
use Prospecter\Core\SharedKernel\Domain\Exception\NotFound;
use Prospecter\Core\SharedKernel\Domain\Exception\NotPossibleInItsState;

/**
 * The only exception a use case throws for a business reason. It types and words the domain's refusal for the
 * client, and keeps the domain exception as the previous one. An infrastructure failure is not translated: it goes
 * through as it is. The domain exception's class names the refusal (`InvalidPhoneNumber` becomes
 * `invalidPhoneNumber`). The refusal also gives what it is about, so that a client can word it in its own language
 * (ADR 0073).
 */
final class UseCaseRefused extends \RuntimeException
{
    public const string WHOLE_REQUEST = '';

    public readonly string $reason;

    /**
     * @param array<string, string> $context what the refusal is about, by name
     */
    private function __construct(
        public readonly ErrorType $type,
        string $message,
        public readonly string $field,
        public readonly array $context,
        BusinessException $previous,
    ) {
        parent::__construct($message, previous: $previous);
        $this->reason = self::reasonOf($previous);
    }

    public static function because(BusinessException $refusal): self
    {
        return match (true) {
            $refusal instanceof NotFound => self::notFound($refusal),
            $refusal instanceof NotAllowed => self::ofTheRule(ErrorType::NotAllowed, $refusal),
            $refusal instanceof NotPossibleInItsState => self::ofTheRule(ErrorType::NotPossibleInItsState, $refusal),
            $refusal instanceof BusinessRuleViolation => self::ofTheRule(ErrorType::BusinessRuleViolated, $refusal),
            default => throw new \LogicException(sprintf('%s is no known kind of refusal', $refusal::class), previous: $refusal),
        };
    }

    private static function notFound(NotFound $refusal): self
    {
        return new self(ErrorType::NotFound, $refusal->getMessage(), self::WHOLE_REQUEST, [], $refusal);
    }

    private static function ofTheRule(ErrorType $type, BusinessRuleViolation $refusal): self
    {
        return new self($type, $refusal->getMessage(), self::fieldOf($refusal), $refusal->context, $refusal);
    }

    /**
     * The refusal's class, as a client names it: `InvalidPhoneNumber` becomes `invalidPhoneNumber`.
     */
    private static function reasonOf(BusinessException $refusal): string
    {
        $class = $refusal::class;

        return lcfirst(substr($class, (int) strrpos($class, '\\') + 1));
    }

    private static function fieldOf(BusinessRuleViolation $refusal): string
    {
        return BusinessRuleViolation::WHOLE === $refusal->field ? self::WHOLE_REQUEST : $refusal->field;
    }
}
