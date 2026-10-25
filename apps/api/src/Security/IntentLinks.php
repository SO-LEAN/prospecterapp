<?php

namespace App\Security;

use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

/**
 * Whether a resource shows the link of an intention (ADR 0027, 0081): its state offers it now, as the core says,
 * and the signed-in member holds the role its controller requires, if any; a link shown is a call accepted.
 */
final readonly class IntentLinks
{
    public function __construct(private AuthorizationCheckerInterface $access)
    {
    }

    /**
     * @param list<string> $offered the intentions the state of the resource offers now, by name
     * @param ?string      $role    the role its controller requires beyond reaching the resource
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
