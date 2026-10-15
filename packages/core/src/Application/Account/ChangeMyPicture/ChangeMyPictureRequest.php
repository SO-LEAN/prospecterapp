<?php

declare(strict_types=1);

namespace Prospecter\Core\Application\Account\ChangeMyPicture;

use Prospecter\Core\Domain\User\UserId;

final readonly class ChangeMyPictureRequest
{
    public function __construct(public UserId $userId, public \SplFileInfo $picture)
    {
    }
}
