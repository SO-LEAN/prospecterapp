<?php

declare(strict_types=1);

namespace Prospecter\Core\SharedKernel\Application\Port;

use Prospecter\Core\SharedKernel\Domain\Exception\UnknownUpload;

/**
 * The files only who may read them reaches (ADR 0077): the client sends a file to the storage as an upload, kept
 * once attached under the identity of its upload, and read by a link signed for a moment.
 */
interface PrivateStorage
{
    /**
     * @throws UnknownUpload
     */
    public function uploaded(string $upload): Upload;

    /**
     * Keeps the uploaded file, which then is no upload any more.
     *
     * @throws UnknownUpload
     */
    public function keep(string $upload): void;

    public function remove(string $file): void;

    /**
     * A link reading the kept file for a moment, downloaded under this name.
     */
    public function downloadLinkOf(string $file, string $name): string;
}
