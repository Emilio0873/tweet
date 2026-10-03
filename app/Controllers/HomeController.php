<?php

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Controller;
use App\Helpers\Url;

class HomeController extends Controller
{
    public function index(): void
    {
        if (Auth::check()) {
            $this->redirect('');
        }
        header('Location: ' . Url::to('login'));
        exit;
    }
}
