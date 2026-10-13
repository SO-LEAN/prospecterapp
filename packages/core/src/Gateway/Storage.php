<?php

declare( strict_types = 1 );

namespace Prospecter\Core\Gateway;

use SplFileInfo;

interface Storage
{
    public function add(SplFileInfo $file): string;
    public function remove(string $url);
}
