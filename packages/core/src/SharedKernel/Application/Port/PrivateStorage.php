<?php

declare(strict_types=1);

namespace Prospecter\Core\SharedKernel\Application\Port;

use Prospecter\Core\SharedKernel\Domain\Exception\UnknownUpload;

/**
 * The files that only their allowed readers reach (ADR 0077). The client sends a file to the storage as an upload.
 * Once the file is attached, the storage keeps it under its upload's id. A short-lived signed link reads it.
 */
interface PrivateStorage
{
    /**
     * @throws UnknownUpload
     */
    public function uploaded(string $upload): Upload;

    /**
     * Keeps the uploaded file, which then stops being an upload.
     *
     * @throws UnknownUpload
     */
    public function keep(string $upload): void;

    public function remove(string $file): void;

    /**
     * A short-lived link that reads the kept file and downloads it under this name.
     */
    public function downloadLinkOf(string $file, string $name): string;
}
