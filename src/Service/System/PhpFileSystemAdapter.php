<?php

namespace App\Service\System;

class PhpFileSystemAdapter implements FileSystemAdapter
{
    /**
     * {@inheritdoc}
     */
    public function tempnam($dir, $prefix)
    {
        return tempnam($dir, $prefix);
    }

    /**
     * {@inheritdoc}
     */
    public function copy($source, $dest, $context = null)
    {
        return copy($source, $dest, $context);
    }

    /**
     * {@inheritdoc}
     */
    public function fileGetContents($filename, $use_include_path = false, $context = null, $offset = 0, $maxlen = null)
    {
        return file_get_contents($filename, $use_include_path, $context, $offset, $maxlen);
    }
}
