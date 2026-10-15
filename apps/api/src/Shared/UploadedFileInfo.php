<?php

namespace App\Shared;

use Symfony\Component\HttpFoundation\File\UploadedFile;

/**
 * Uploaded file handed to the use cases: PHP stores uploads under a temporary
 * name without extension, so the real one is guessed from the content.
 */
final class UploadedFileInfo extends \SplFileInfo
{
    private readonly string $extension;

    public function __construct(UploadedFile $file)
    {
        parent::__construct($file->getPathname());

        $this->extension = $file->guessExtension() ?? $file->getClientOriginalExtension();
    }

    public function getExtension(): string
    {
        return $this->extension;
    }
}
