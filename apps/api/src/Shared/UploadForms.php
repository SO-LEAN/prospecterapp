<?php

namespace App\Shared;

use App\Shared\Resource\UploadResource;

/**
 * The forms a browser sends a private file to the storage with, itself (ADR 0077).
 */
interface UploadForms
{
    public function prepareUpload(): UploadResource;
}
