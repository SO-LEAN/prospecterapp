<?php

declare(strict_types=1);

namespace App\Security;

use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

/**
 * Whether a resource shows an intent's link (ADR 0027, 0081). The resource's state must offer the intent now, as the
 * core says. The signed-in member must hold the role that its controller requires, if any. So a shown link is an
 * accepted call.
 */
final readonly class IntentLinks
{
    public function __construct(private AuthorizationCheckerInterface $access)
    {
    }

    /**
     * @param list<string> $offered the names of the intents that the resource's state offers now
     * @param ?string      $role    the role that the intent's controller requires besides reaching the resource
     */
    public function shows(string $intent, array $offered, ?string $role = null): bool
    {
        return in_array($intent, $offered, true) && $this->holds($role);
    }

    public function holds(?string $role): bool
    {
        return null === $role || $this->access->isGranted($role);
    }
}
