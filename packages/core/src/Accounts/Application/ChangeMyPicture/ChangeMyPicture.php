<?php

declare(strict_types=1);

namespace Prospecter\Core\Accounts\Application\ChangeMyPicture;

use Prospecter\Core\SharedKernel\Domain\MemberId;

final readonly class ChangeMyPicture
{
    public function __construct(public MemberId $userId, public \SplFileInfo $picture)
    {
    }
}
