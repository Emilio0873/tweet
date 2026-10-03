<?php

namespace App\Helpers;

class Url
{
    public static function to(string $path = ''): string
    {
        $app = require dirname(__DIR__, 2) . '/config/app.php';
        return rtrim($app['url'], '/') . '/' . ltrim($path, '/');
    }

    public static function asset(string $path): string
    {
        return self::to('assets/' . ltrim($path, '/'));
    }

    public static function upload(string $path): string
    {
        return self::to('uploads/' . ltrim($path, '/'));
    }
}
