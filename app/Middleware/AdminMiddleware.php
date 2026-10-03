<?php

namespace App\Middleware;

use App\Core\Auth;
use App\Helpers\Url;

class AdminMiddleware
{
    public function handle(): bool
    {
        if (!Auth::check() || !Auth::isAdmin()) {
            header('Location: ' . Url::to('chat'));
            return false;
        }
        return true;
    }
}
