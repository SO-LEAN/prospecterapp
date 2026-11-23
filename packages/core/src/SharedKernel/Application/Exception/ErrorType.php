<?php

declare(strict_types=1);

namespace Prospecter\Core\SharedKernel\Application\Exception;

/**
 * The kind of refusal a use case returns, so that a client handles every refusal the same way (a status code, a
 * message) without knowing the domain.
 */
enum ErrorType
{
    /** Unknown, or the caller cannot reach it. */
    case NotFound;
    /** The caller has no right to this intention. */
    case NotAllowed;
    /** The current state does not allow the intention. */
    case NotPossibleInItsState;
    /** A business rule refuses the request, naming the faulty field when there is one. */
    case BusinessRuleViolated;
}
