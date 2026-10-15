<?php

declare(strict_types=1);

namespace Tests\Core\Double;

use Prospecter\Core\Gateway\UserNotifier;

final class CollectedNotifications implements UserNotifier
{
    /** @var list<array{string, string}> [level, message] */
    private array $notifications = [];

    public function addSuccess(string $message): void
    {
        $this->notifications[] = ['success', $message];
    }

    public function addWarning(string $message): void
    {
        $this->notifications[] = ['warning', $message];
    }

    public function addError(string $message): void
    {
        $this->notifications[] = ['error', $message];
    }

    public function addInfo(string $message): void
    {
        $this->notifications[] = ['info', $message];
    }

    /**
     * @return list<array{string, string}>
     */
    public function all(): array
    {
        return $this->notifications;
    }
}
