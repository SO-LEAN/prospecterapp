<?php

namespace App\Shared;

use App\Shared\Resource\UploadResource;
use Symfony\Component\HttpFoundation\File\File;

/**
 * What the API sends to the private bucket itself, with the form of an upload, as a browser does (ADR 0172).
 */
interface UploadSender
{
    public function send(UploadResource $form, File $file): void;
}
