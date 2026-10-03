<?php

namespace App\Middleware;

use App\Core\Auth;
use App\Helpers\Url;

class GuestMiddleware
{
    public function handle(): bool
    {
        if (Auth::check()) {
            header('Location: ' . Url::to(''));
            return false;
        }
        return true;
    }
}
