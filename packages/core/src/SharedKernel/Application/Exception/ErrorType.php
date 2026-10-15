<?php

declare(strict_types=1);

namespace Prospecter\Core\SharedKernel\Application\Exception;

/**
 * What kind of refusal a use case answers, so that a client handles them all the same way (a status code, a
 * message) without knowing the domain.
 */
enum ErrorType
{
    /** Unknown, or not reachable by who asks. */
    case NotFound;
    /** Who asks has no right to this intention. */
    case NotAllowed;
    /** The intention is not offered in the current state. */
    case NotPossibleInItsState;
    /** A business rule refuses the request, naming the faulty field when there is one. */
    case BusinessRuleViolated;
}
