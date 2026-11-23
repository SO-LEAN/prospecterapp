<?php

declare(strict_types=1);

namespace App\Shared;

use Symfony\Component\HttpFoundation\File\UploadedFile;

/**
 * An uploaded file handed to the use cases. PHP stores an upload under a temporary name without an extension, so the
 * class guesses the real one from the content.
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
