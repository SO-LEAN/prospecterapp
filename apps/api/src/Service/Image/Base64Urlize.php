<?php

namespace App\Service\Image;

trait Base64Urlize
{
    protected function urlize(string $data): string
    {
        return rtrim(strtr($data, '+/', '-_'), '=');
    }

    protected function unurlize(string $data): string
    {
        return strtr($data, '-_', '+/').str_repeat('=', 3 - (3 + strlen($data)) % 4);
    }
}
