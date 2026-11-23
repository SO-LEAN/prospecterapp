<?php

declare(strict_types=1);

namespace App\Shared;

use App\Shared\Resource\UploadResource;

/**
 * The forms a browser uses to send a private file to the storage itself (ADR 0077).
 */
interface UploadForms
{
    public function prepareUpload(): UploadResource;
}
