<?php

namespace App\Shared\Persistence;

use Prospecter\Core\SharedKernel\Domain\File;

/**
 * @extends ValueObjectJsonType<File>
 */
final class FileType extends ValueObjectJsonType
{
    protected function toArray(object $value): array
    {
        return ['url' => $value->url, 'extension' => $value->extension, 'size' => $value->size];
    }

    protected function fromArray(array $data): File
    {
        return new File($data['url'], $data['extension'], $data['size']);
    }
}
