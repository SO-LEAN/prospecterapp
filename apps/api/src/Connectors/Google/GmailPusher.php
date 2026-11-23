<?php

declare(strict_types=1);

namespace App\Connectors\Google;

use App\Shared\Typed;
use Symfony\Component\Security\Core\User\UserInterface;

/**
 * The one who pushes Gmail notifications to an account. It is the service account of the account's subscription at
 * Google, not a member (ADR 0154).
 */
final readonly class GmailPusher implements UserInterface
{
    public const string ROLE = 'ROLE_GMAIL_PUSHER';

    public function __construct(private string $account)
    {
    }

    public function getRoles(): array
    {
        return [self::ROLE];
    }

    public function getUserIdentifier(): string
    {
        return Typed::filledString($this->account);
    }
}
