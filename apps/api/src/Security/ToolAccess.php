<?php

declare(strict_types=1);

namespace App\Security;

use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Security\Core\Authorization\AccessDecision;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

/**
 * Tells who calls an MCP server tool, and makes the checks that the `#[IsGranted]` attributes make on the adapted
 * controller (ADR 0163). It checks the member's role and, through a resource's voter, their access to it. It refuses
 * as the endpoint refuses.
 */
final readonly class ToolAccess
{
    public function __construct(private Security $security)
    {
    }

    public function member(): User
    {
        $member = $this->security->getUser();
        \assert($member instanceof User);

        return $member;
    }

    public function denyUnlessGranted(string $attribute, mixed $subject = null): void
    {
        $decision = new AccessDecision();

        if ($this->security->isGranted($attribute, $subject, $decision)) {
            return;
        }

        $denial = new AccessDeniedException($decision->getMessage());
        $denial->setAttributes([$attribute]);
        $denial->setSubject($subject);
        $denial->setAccessDecision($decision);

        throw $denial;
    }
}
