<?php

declare(strict_types=1);

namespace App\Shared;

use App\Shared\Resource\UploadResource;
use Symfony\Component\HttpFoundation\File\File;

/**
 * Sends a file to the private bucket itself, with an upload form, as a browser does (ADR 0172).
 */
interface UploadSender
{
    public function upload(UploadResource $form, File $file): void;
}
