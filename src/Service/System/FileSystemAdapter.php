<?php

namespace App\Service\System;

interface FileSystemAdapter
{
    /**
     * @param $dir
     * @param $prefix
     *
     * @return bool|string the new temporary filename, or false on
     */
    public function tempnam($dir, $prefix);

    /**
     * @param $source
     * @param $dest
     * @param null $context
     *
     * @return bool true on success or false on failure
     */
    public function copy($source, $dest, $context = null);

    /**
     * @param $filename
     * @param bool $use_include_path
     * @param null $context
     * @param int  $offset
     * @param null $maxlen
     *
     * @return string|bool the function returns the read data or false on failure
     */
    public function fileGetContents($filename, $use_include_path = false, $context = null, $offset = 0, $maxlen = null);
}
