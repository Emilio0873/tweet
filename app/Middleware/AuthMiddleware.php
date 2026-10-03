<?php

namespace App\Middleware;

use App\Core\Auth;
use App\Helpers\Url;

class AuthMiddleware
{
    public function handle(): bool
    {
        if (!Auth::check()) {
            header('Location: ' . Url::to('login'));
            return false;
        }
        return true;
    }
}
