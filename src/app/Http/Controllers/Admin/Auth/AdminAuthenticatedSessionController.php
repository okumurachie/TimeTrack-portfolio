<?php

namespace App\Http\Controllers\Admin\Auth;

use Laravel\Fortify\Http\Controllers\AuthenticatedSessionController as FortifySessionController;
use Illuminate\Support\Facades\Auth;

class AdminAuthenticatedSessionController extends FortifySessionController
{
    protected function guard()
    {
        return Auth::guard('admin');
    }
}
