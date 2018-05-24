<?php

namespace App\Service\System;


class PhpFileSystemAdapter implements FileSystemAdapter
{
    /**
     * @inheritDoc
     */
    function tempnam($dir, $prefix)
    {
        return tempnam($dir, $prefix);
    }

    /**
     * @inheritDoc
     */
    public function copy($source, $dest, $context = null)
    {
        return copy($source, $dest, $context);
    }

    /**
     * @inheritDoc
     */
    public function fileGetContents($filename, $use_include_path = false, $context = null, $offset = 0, $maxlen = null)
    {
        return file_get_contents($filename, $use_include_path, $context, $offset, $maxlen);
    }
}
